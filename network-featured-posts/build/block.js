/* global wp */
( function () {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, useBlockProps, FontSizePicker, ColorPalette } = wp.blockEditor;
    const {
        PanelBody,
        RangeControl,
        ToggleControl,
        SelectControl,
        TextControl,
        ComboboxControl,
        Button,
        BaseControl,
        Spinner,
        Notice,
    } = wp.components;
    const { Fragment, useEffect, useMemo, useRef, useState } = wp.element;
    const ServerSideRender = wp.serverSideRender;
    const apiFetch = wp.apiFetch;

    const uniqInts = ( arr ) => {
        const out = [];
        const seen = new Set();
        ( arr || [] ).forEach( ( n ) => {
            const v = parseInt( n, 10 );
            if ( Number.isFinite( v ) && v > 0 && ! seen.has( v ) ) {
                seen.add( v );
                out.push( v );
            }
        } );
        return out;
    };

    const parseCSV = ( text ) =>
        ( text || '' )
            .split( ',' )
            .map( ( s ) => s.trim() )
            .filter( ( s ) => s.length );

    const META_LABELS = {
        site: 'Site name',
        author: 'Author',
        date: 'Date',
        categories: 'Categories',
    };

    const META_KEYS = [ 'site', 'author', 'date', 'categories' ];

    const normalizeMetaOrder = ( order ) => {
        const seen = new Set();
        const out = [];
        ( Array.isArray( order ) ? order : [] ).forEach( ( k ) => {
            if ( META_KEYS.includes( k ) && ! seen.has( k ) ) {
                seen.add( k );
                out.push( k );
            }
        } );
        META_KEYS.forEach( ( k ) => {
            if ( ! seen.has( k ) ) out.push( k );
        } );
        return out;
    };

    const enabledForKey = ( meta, key ) => {
        if ( ! meta ) return false;
        if ( key === 'site' ) return !! meta.showSite;
        if ( key === 'author' ) return !! meta.showAuthor;
        if ( key === 'date' ) return !! meta.showDate;
        if ( key === 'categories' ) return !! meta.showCategories;
        return false;
    };

    const togglePatchForKey = ( key, nextValue ) => {
        if ( key === 'site' ) return { showSite: nextValue };
        if ( key === 'author' ) return { showAuthor: nextValue };
        if ( key === 'date' ) return { showDate: nextValue };
        if ( key === 'categories' ) return { showCategories: nextValue };
        return {};
    };

    registerBlockType( 'nfp/network-featured-posts', {
        edit: function ( props ) {
            const { attributes, setAttributes } = props;

            const {
                postsToShow,
                showFeaturedImage,
                imageSize,
                layout,
                columns,
                categorySlugs,
                excludeCategorySlugs,
                includeBlogIds,
                postType,
                dateRange,
                orderBy,
                sortOrder,
                meta,
                metaStyle,
                metaOrder,
            } = attributes;

            const blockProps = useBlockProps();

            const setMeta = ( patch ) =>
                setAttributes( { meta: Object.assign( {}, meta, patch ) } );

            const setMetaStyle = ( patch ) =>
                setAttributes( { metaStyle: Object.assign( {}, metaStyle, patch ) } );

            const setMetaOrder = ( next ) =>
                setAttributes( { metaOrder: normalizeMetaOrder( next ) } );

            // --- Site picker ---
            const [ categorySlugsInput, setCategorySlugsInput ] = useState(
    Array.isArray( categorySlugs ) ? categorySlugs.join( ',' ) : ''
);
const [ excludeCategorySlugsInput, setExcludeCategorySlugsInput ] = useState(
    Array.isArray( excludeCategorySlugs ) ? excludeCategorySlugs.join( ',' ) : ''
);

// Keep inputs in sync when attributes change externally (e.g., undo/redo).
useEffect( () => {
    const next = Array.isArray( categorySlugs ) ? categorySlugs.join( ',' ) : '';
    if ( next !== categorySlugsInput ) {
        setCategorySlugsInput( next );
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
}, [ JSON.stringify( categorySlugs ) ] );

useEffect( () => {
    const next = Array.isArray( excludeCategorySlugs ) ? excludeCategorySlugs.join( ',' ) : '';
    if ( next !== excludeCategorySlugsInput ) {
        setExcludeCategorySlugsInput( next );
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
}, [ JSON.stringify( excludeCategorySlugs ) ] );

const [ siteSearch, setSiteSearch ] = useState( '' );
            const [ siteResults, setSiteResults ] = useState( [] );
            const [ siteLoading, setSiteLoading ] = useState( false );
            const [ siteError, setSiteError ] = useState( '' );
            const debounceRef = useRef( null );

            const fetchSites = ( searchTerm ) => {
                setSiteLoading( true );
                setSiteError( '' );
                const qs = new URLSearchParams();
                if ( searchTerm ) qs.set( 'search', searchTerm );
                qs.set( 'per_page', '50' );
                qs.set( 'page', '1' );

                apiFetch( { path: `/nfp/v1/sites?${ qs.toString() }` } )
                    .then( ( data ) => {
                        const items = data && data.items ? data.items : [];
                        setSiteResults( items );
                        setSiteLoading( false );
                    } )
                    .catch( ( err ) => {
                        setSiteLoading( false );
                        setSiteResults( [] );
                        setSiteError(
                            err && err.message ? err.message : 'Failed to load sites.'
                        );
                    } );
            };

            useEffect( () => {
                fetchSites( '' );
                // eslint-disable-next-line react-hooks/exhaustive-deps
            }, [] );

            useEffect( () => {
                if ( debounceRef.current ) clearTimeout( debounceRef.current );
                debounceRef.current = setTimeout( () => fetchSites( siteSearch ), 250 );
                return () => debounceRef.current && clearTimeout( debounceRef.current );
                // eslint-disable-next-line react-hooks/exhaustive-deps
            }, [ siteSearch ] );

            const siteOptions = useMemo(
                () =>
                    ( siteResults || [] ).map( ( s ) => ( {
                        value: String( s.blogId ),
                        label: `${ s.name } (ID ${ s.blogId })`,
                    } ) ),
                [ siteResults ]
            );

            const selectedBlogIds = uniqInts( includeBlogIds );

            const addBlogId = ( val ) => {
                const v = parseInt( val, 10 );
                if ( ! Number.isFinite( v ) || v <= 0 ) return;
                setAttributes( { includeBlogIds: uniqInts( [ ...selectedBlogIds, v ] ) } );
            };

            const removeBlogId = ( val ) => {
                const v = parseInt( val, 10 );
                setAttributes( {
                    includeBlogIds: ( selectedBlogIds || [] ).filter( ( x ) => x !== v ),
                } );
            };

            const selectedSitesLabel = useMemo( () => {
                if ( ! selectedBlogIds.length ) return 'All allowed sites';
                return `${ selectedBlogIds.length } selected`;
            }, [ selectedBlogIds ] );

            // --- Meta drag/drop ---
            const order = normalizeMetaOrder( metaOrder );
            const dragFrom = useRef( null );

            const onDragStart = ( index ) => ( event ) => {
                dragFrom.current = index;
                event.dataTransfer.effectAllowed = 'move';
                try {
                    event.dataTransfer.setData( 'text/plain', String( index ) );
                } catch ( e ) {}
            };

            const onDragOver = () => ( event ) => {
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
            };

            const onDrop = ( index ) => ( event ) => {
                event.preventDefault();
                const from = dragFrom.current;
                dragFrom.current = null;
                if ( from === null || from === undefined ) return;
                if ( from === index ) return;

                const next = [ ...order ];
                const [ moved ] = next.splice( from, 1 );
                next.splice( index, 0, moved );
                setMetaOrder( next );
            };
            const fontSizes = useMemo(
                () => [
                    { name: 'Small', slug: 'small', size: 12 },
                    { name: 'Normal', slug: 'normal', size: 14 },
                    { name: 'Large', slug: 'large', size: 16 },
                    { name: 'XL', slug: 'xlarge', size: 18 },
                ],
                []
            );

            return wp.element.createElement(
                Fragment,
                null,
                wp.element.createElement(
                    InspectorControls,
                    null,

                    wp.element.createElement(
                        PanelBody,
                        { title: 'Query', initialOpen: true },

                        wp.element.createElement( RangeControl, {
                            label: 'Posts to show',
                            min: 1,
                            max: 24,
                            value: postsToShow,
                            onChange: ( v ) => setAttributes( { postsToShow: v } ),
                        } ),
wp.element.createElement( SelectControl, {
    label: 'Time period',
    value: dateRange,
    options: [
        { label: 'All time', value: 'all' },
        { label: 'Last 30 days', value: '30d' },
        { label: 'Last 90 days', value: '90d' },
        { label: 'Last 6 months', value: '6m' },
        { label: 'Last year', value: '1y' },
    ],
    onChange: ( v ) => setAttributes( { dateRange: v } ),
} ),

wp.element.createElement( SelectControl, {
    label: 'Order by',
    value: orderBy,
    options: [
        { label: 'Date', value: 'date' },
        { label: 'Title', value: 'title' },
    ],
    onChange: ( v ) => setAttributes( { orderBy: v } ),
} ),

wp.element.createElement( SelectControl, {
    label: 'Sort order',
    value: sortOrder,
    options: [
        { label: 'Descending', value: 'desc' },
        { label: 'Ascending', value: 'asc' },
    ],
    onChange: ( v ) => setAttributes( { sortOrder: v } ),
} ),


                        wp.element.createElement( SelectControl, {
                            label: 'Post type',
                            value: postType,
                            options: [ { label: 'Posts', value: 'post' } ],
                            onChange: ( v ) => setAttributes( { postType: v } ),
                            help: 'This version indexes post type "post".',
                        } ),

                        wp.element.createElement( TextControl, {
                                label: 'Include category slugs (comma-separated)',
                                help: 'Example: news,events,featured',
                                value: categorySlugsInput,
                                onChange: ( val ) => {
                                    setCategorySlugsInput( val );
                                    setAttributes( { categorySlugs: parseCSV( val ) } );
                                },
                            } ),

                        wp.element.createElement( TextControl, {
                                label: 'Exclude category slugs (comma-separated)',
                                help: 'Example: internal,private',
                                value: excludeCategorySlugsInput,
                                onChange: ( val ) => {
                                    setExcludeCategorySlugsInput( val );
                                    setAttributes( { excludeCategorySlugs: parseCSV( val ) } );
                                },
                            } ),

                        wp.element.createElement(
                            BaseControl,
                            {
                                label: `Sites (${ selectedSitesLabel })`,
                                help:
                                    'Leave empty to use all sites allowed by Network Admin settings.',
                            },
                            wp.element.createElement( TextControl, {
                                label: 'Search sites',
                                value: siteSearch,
                                onChange: ( v ) => setSiteSearch( v ),
                            } ),
                            siteLoading ? wp.element.createElement( Spinner, null ) : null,
                            siteError
                                ? wp.element.createElement(
                                        Notice,
                                        { status: 'error', isDismissible: false },
                                        siteError
                                  )
                                : null,
                            wp.element.createElement( ComboboxControl, {
                                label: 'Add a site',
                                value: '',
                                options: siteOptions,
                                onChange: ( v ) => addBlogId( v ),
                                allowReset: true,
                            } ),
                            wp.element.createElement(
                                'div',
                                { style: { marginTop: '8px' } },
                                selectedBlogIds.length
                                    ? selectedBlogIds.map( ( id ) =>
                                            wp.element.createElement(
                                                'div',
                                                {
                                                    key: id,
                                                    style: {
                                                        display: 'flex',
                                                        gap: '8px',
                                                        alignItems: 'center',
                                                        marginBottom: '6px',
                                                    },
                                                },
                                                wp.element.createElement( 'code', null, String( id ) ),
                                                wp.element.createElement(
                                                    Button,
                                                    {
                                                        isSecondary: true,
                                                        onClick: () => removeBlogId( id ),
                                                    },
                                                    'Remove'
                                                )
                                            )
                                      )
                                    : wp.element.createElement( 'em', null, 'All allowed sites' )
                            ),
                            wp.element.createElement( TextControl, {
                                label: 'Or set site IDs directly (comma-separated)',
                                value: selectedBlogIds.join( ',' ),
                                onChange: ( val ) =>
                                    setAttributes( { includeBlogIds: uniqInts( parseCSV( val ) ) } ),
                            } )
                        )
                    ),

                    wp.element.createElement(
                        PanelBody,
                        { title: 'Layout', initialOpen: false },
                        wp.element.createElement( SelectControl, {
                            label: 'Layout type',
                            value: layout,
                            options: [
                                { label: 'Columns (Grid)', value: 'grid' },
                                { label: 'Masonry (CSS Columns)', value: 'masonry' },
                            ],
                            onChange: ( v ) => setAttributes( { layout: v } ),
                        } ),
                        wp.element.createElement( RangeControl, {
                            label: 'Columns',
                            min: 1,
                            max: 6,
                            value: columns,
                            onChange: ( v ) => setAttributes( { columns: v } ),
                        } )
                    ),

                    wp.element.createElement(
                        PanelBody,
                        { title: 'Featured Image', initialOpen: false },
                        wp.element.createElement( ToggleControl, {
                            label: 'Show featured image',
                            checked: !! showFeaturedImage,
                            onChange: ( v ) => setAttributes( { showFeaturedImage: !! v } ),
                        } ),
                        wp.element.createElement( SelectControl, {
                            label: 'Image size',
                            value: imageSize,
                            options: [
                                { label: 'Thumbnail', value: 'thumbnail' },
                                { label: 'Medium', value: 'medium' },
                                { label: 'Large', value: 'large' },
                                { label: 'Full', value: 'full' },
                            ],
                            onChange: ( v ) => setAttributes( { imageSize: v } ),
                            disabled: ! showFeaturedImage,
                        } )
                    ),

                    wp.element.createElement(
                        PanelBody,
                        { title: 'Meta', initialOpen: false },

                        wp.element.createElement( ToggleControl, {
                            label: 'Show excerpt',
                            checked: !! ( meta && meta.showExcerpt ),
                            onChange: ( v ) => setMeta( { showExcerpt: !! v } ),
                        } ),

                        wp.element.createElement( SelectControl, {
                            label: 'Meta position',
                            value: meta && meta.position ? meta.position : 'below',
                            options: [
                                { label: 'Above excerpt', value: 'above' },
                                { label: 'Below excerpt', value: 'below' },
                            ],
                            onChange: ( v ) => setMeta( { position: v } ),
                        } ),

                        wp.element.createElement( TextControl, {
                            label: 'Meta separator',
                            value: meta && meta.separator ? meta.separator : ' • ',
                            onChange: ( v ) => setMeta( { separator: v } ),
                        } ),

                        wp.element.createElement( RangeControl, {
                            label: 'Meta opacity',
                            min: 0.3,
                            max: 1.0,
                            step: 0.05,
                            value:
                                metaStyle && typeof metaStyle.opacity === 'number'
                                    ? metaStyle.opacity
                                    : 0.8,
                            onChange: ( v ) => setMetaStyle( { opacity: v } ),
                        } ),

                        wp.element.createElement( ToggleControl, {
                            label: 'Uppercase meta',
                            checked: !! ( metaStyle && metaStyle.uppercase ),
                            onChange: ( v ) => setMetaStyle( { uppercase: !! v } ),
                        } ),

                        wp.element.createElement(
                            BaseControl,
                            { label: 'Meta text size' },
                            wp.element.createElement( FontSizePicker, {
                                fontSizes: fontSizes,
                                value:
                                    metaStyle && typeof metaStyle.fontSize === 'number'
                                        ? metaStyle.fontSize
                                        : 14,
                                onChange: ( v ) =>
                                    setMetaStyle( { fontSize: parseInt( v, 10 ) || 14 } ),
                                withReset: true,
                            } )
                        ),

                        wp.element.createElement(
                            BaseControl,
                            { label: 'Meta text color' },
                            wp.element.createElement( ColorPalette, {
                                value: metaStyle && metaStyle.textColor ? metaStyle.textColor : '',
                                onChange: ( v ) => setMetaStyle( { textColor: v || '' } ),
                                clearable: true,
                            } )
                        ),

                        wp.element.createElement(
                            BaseControl,
                            {
                                label: 'Meta fields (drag to reorder)',
                                help: 'Drag items to set display order; toggles control visibility.',
                            },
                            wp.element.createElement(
                                'div',
                                { style: { display: 'grid', gap: '8px', marginTop: '8px' } },
                                order.map( ( key, index ) => {
                                    const enabled = enabledForKey( meta, key );
                                    return wp.element.createElement(
                                        'div',
                                        {
                                            key: key,
                                            draggable: true,
                                            onDragStart: onDragStart( index ),
                                            onDragOver: onDragOver(),
                                            onDrop: onDrop( index ),
                                            style: {
                                                border: '1px solid rgba(0,0,0,0.12)',
                                                borderRadius: '8px',
                                                padding: '8px',
                                                background: 'rgba(0,0,0,0.02)',
                                                display: 'grid',
                                                gridTemplateColumns: '1fr',
                                                gap: '8px',
                                                alignItems: 'center',
                                            },
                                        },
                                        wp.element.createElement(
                                            'div',
                                            { style: { display: 'grid', gap: '6px' } },
                                            wp.element.createElement(
                                                'strong',
                                                null,
                                                META_LABELS[ key ] || key
                                            ),
                                            wp.element.createElement( ToggleControl, {
                                                label: 'Show',
                                                checked: enabled,
                                                onChange: ( v ) =>
                                                    setMeta( togglePatchForKey( key, !! v ) ),
                                            } )
                                        )

                                    );
                                } )
                            )
                        )
                    )
                ),

                wp.element.createElement(
                    'div',
                    blockProps,
                    wp.element.createElement( ServerSideRender, {
                        block: 'nfp/network-featured-posts',
                        attributes: attributes,
                        key: JSON.stringify( {
                            postsToShow,
                            showFeaturedImage,
                            imageSize,
                            layout,
                            columns,
                            categorySlugs,
                            excludeCategorySlugs,
                            includeBlogIds,
                            postType,
                            dateRange,
                            orderBy,
                            sortOrder,
                            meta,
                            metaStyle,
                            metaOrder,
                        } ),
                    } )
                )
            );
        },

        save: function () {
            return null;
        },
    } );
} )();
