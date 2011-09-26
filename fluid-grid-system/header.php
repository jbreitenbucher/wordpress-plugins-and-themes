<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo('charset'); ?> " />
<meta name="generator" content="WordPress <?php bloginfo('version'); ?>" /> 
	<link rel="shortcut icon" href="<?php bloginfo('siteurl'); ?>/wp-content/themes/treacle-jb/images/favicon.ico"/>
	<link rel="stylesheet" href="stylesheets/fluid.gs.css" type="text/css" media="screen" title="no title" charset="utf-8">
	<!--[if lt IE 8]><link rel="stylesheet" href="stylesheets/fluid.gs.lt_ie8.css" type="text/css" media="screen" title="no title" charset="utf-8"><![endif]-->
	<link rel="stylesheet" href="stylesheets/demo.css" type="text/css" media="screen" title="no title" charset="utf-8">
	<link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="<?php bloginfo('rss2_url'); ?>" />
	<link rel="alternate" type="text/xml" title="RSS .92" href="<?php bloginfo('rss_url'); ?>" />
	<link rel="alternate" type="application/atom+xml" title="Atom 0.3" href="<?php bloginfo('atom_url'); ?>" />
	<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
	<!--<script type="text/javascript" src="http://use.typekit.com/bkq6xcq.js"></script>
	<script type="text/javascript">try{Typekit.load();}catch(e){}</script>-->
	<link href="./wowhead/css/wowhead.css" rel="stylesheet" type="text/css" />
	<script src="http://www.wowhead.com/widgets/power.js"></script>
	<link href="./wowhead/css/armory.css" rel="stylesheet" type="text/css" />
	<script src="./wowhead/js/armory.js.php" type="text/javascript"></script>
<title><?php bloginfo('name'); ?> <?php if ( is_single() ) { ?> : Blog Archive <?php } ?> <?php wp_title(':'); ?></title>
<?php // comments_popup_script(); // off by default ?>
<?php wp_head(); ?>
</head>

<body>
<?php if(function_exists('wp_admin_bar')) wp_admin_bar(); ?>
<div id="page-container" class="fluid_grid_layout">
<!--Header-->    
  <div id="header-container" class="six_column section">
    <div id="header" class="six column">
      <div class="column_content">
      	<h1><a href="<?php echo get_settings('home'); ?>"><?php bloginfo('name'); ?></a></h1>
      	<h2><?php bloginfo('description'); ?> </h2>
      </div>
    </div>
  </div>