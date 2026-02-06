<?php

namespace Wooster\LdapAuth\Ldap;

use Wooster\LdapAuth\Options;
use Wooster\LdapAuth\Support\Logger;

defined('ABSPATH') || exit;

final class GroupChecker
{
    private const MAX_DEPTH = 10;

    private Directory $directory;

    public function __construct(?Directory $directory = null)
    {
        $this->directory = $directory ?: new Directory();
    }

    /**
     * @param string $userDn
     * @return array{allowed:bool, reason:string}
     */
    public function check_login(string $userDn): array
    {
        $deny = Options::get('ldapGroupDenyLogin', []);
        $allow = Options::get('ldapGroupAllowLogin', []);

        $deny = is_array($deny) ? array_map('strtolower', $deny) : [];
        $allow = is_array($allow) ? array_map('strtolower', $allow) : [];

        if (!empty($deny) && $this->is_in_groups($userDn, $deny)) {
            return ['allowed' => false, 'reason' => 'deny_group'];
        }

        if (!empty($allow)) {
            if ($this->is_in_groups($userDn, $allow)) {
                return ['allowed' => true, 'reason' => 'allow_group'];
            }
            return ['allowed' => false, 'reason' => 'not_in_allow_group'];
        }

        return ['allowed' => true, 'reason' => 'no_group_restrictions'];
    }

    /**
     * @param string $userDn
     * @param string[] $requiredGroups Lowercased group DNs
     */
    public function is_in_groups(string $userDn, array $requiredGroups): bool
    {
        $requiredGroups = array_values(array_unique(array_map('strtolower', $requiredGroups)));
        if (empty($requiredGroups)) {
            return false;
        }

        $conn = (new ConnectionFactory())->connect();
        if (!$conn) {
            return false;
        }
        $client = new Client($conn);
        // Bind service or anonymous for group lookups.
        $dir = new Directory();
        if (!$dir->bind_service($client)) {
            $client->bind_anonymous();
        }

        $baseDn = trim(Options::get_string('ldapServerOU'));
        if ($baseDn === '') {
            return false;
        }

        $dnAttr = Options::get_string('ldapAttributeDN', 'dn');
        $memberAttr = Options::get_bool('ldapLinuxWindows')
            ? Options::get_string('ldapAttributeMemberNix', 'uniquemember')
            : Options::get_string('ldapAttributeMember', 'member');
        $groupObjClass = Options::get_bool('ldapLinuxWindows')
            ? Options::get_string('ldapAttributeGroupObjectclassNix', 'groupofuniquenames')
            : Options::get_string('ldapAttributeGroupObjectclass', 'group');

        // Direct membership: (&(member=<userDN>)(objectclass=<group>))
        $filter = '(&(' . $memberAttr . '=' . ldap_escape($userDn, '', LDAP_ESCAPE_FILTER) . ')(objectclass=' . ldap_escape($groupObjClass, '', LDAP_ESCAPE_FILTER) . '))';
        $groups = $client->search_all($baseDn, $filter, [$dnAttr]);
        $userGroups = [];
        foreach ($groups as $g) {
            $dn = '';
            if (isset($g['dn'])) {
                $dn = strtolower((string) $g['dn']);
            } elseif (isset($g[strtolower($dnAttr)])) {
                $dn = strtolower((string) ($g[strtolower($dnAttr)][0] ?? ''));
            }
            if ($dn) {
                $userGroups[] = $dn;
                if (in_array($dn, $requiredGroups, true)) {
                    return true;
                }
            }
        }

        // Nested groups: walk "groups that contain groupDN".
        return $this->check_nested($client, $baseDn, $requiredGroups, $userGroups, 0, []);
    }

    /**
     * @param Client $client
     * @param string $baseDn
     * @param string[] $required
     * @param string[] $toCheck
     * @param int $depth
     * @param array<string,bool> $visited
     */
    private function check_nested(Client $client, string $baseDn, array $required, array $toCheck, int $depth, array $visited): bool
    {
        if (empty($toCheck)) {
            return false;
        }
        if ($depth >= self::MAX_DEPTH) {
            Logger::debug('Nested group check max depth reached', ['depth' => $depth]);
            return false;
        }

        $dnAttr = Options::get_string('ldapAttributeDN', 'dn');
        $memberAttr = Options::get_string('ldapAttributeMember', 'member');
        $groupObjClass = Options::get_string('ldapAttributeGroupObjectclass', 'group');

        $next = [];
        foreach ($toCheck as $groupDn) {
            $groupDn = strtolower($groupDn);
            if (isset($visited[$groupDn])) {
                continue;
            }
            $visited[$groupDn] = true;

            $filter = '(&(' . $memberAttr . '=' . ldap_escape($groupDn, '', LDAP_ESCAPE_FILTER) . ')(objectclass=' . ldap_escape($groupObjClass, '', LDAP_ESCAPE_FILTER) . '))';
            $entries = $client->search_all($baseDn, $filter, [$dnAttr]);
            foreach ($entries as $e) {
                $dn = '';
                if (isset($e['dn'])) {
                    $dn = strtolower((string) $e['dn']);
                } elseif (isset($e[strtolower($dnAttr)])) {
                    $dn = strtolower((string) ($e[strtolower($dnAttr)][0] ?? ''));
                }
                if ($dn === '') {
                    continue;
                }
                if (in_array($dn, $required, true)) {
                    return true;
                }
                $next[] = $dn;
            }
        }

        $next = array_values(array_unique($next));
        return $this->check_nested($client, $baseDn, $required, $next, $depth + 1, $visited);
    }
}
