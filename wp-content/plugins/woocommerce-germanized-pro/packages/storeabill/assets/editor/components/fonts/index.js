import { withSelect, withDispatch } from '@wordpress/data';
import {__, _x} from '@wordpress/i18n';
import { compose } from '@wordpress/compose';
import { get, isEmpty, cloneDeep, merge } from 'lodash';
import { getFonts, getFont, getSetting } from '@storeabill/settings';
import { Component, createRef } from "@wordpress/element";
import { Button, SelectControl } from "@wordpress/components";

/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './editor.scss';
import {TextControl} from "wordpress-components";

const stopKeyPropagation = ( event ) => event.stopPropagation();

class FontSelect extends Component {

    constructor() {
        super( ...arguments );

        this.state = {
            filterValue: '',
            hoveredItem: null,
            filteredItems: [],
            showResults: false,
        };

        this.availableFonts = getFonts();
        this.onChangeSearchInput = this.onChangeSearchInput.bind( this );
        this.onChangeFont = this.onChangeFont.bind( this );
        this.onChangeFontVariants = this.onChangeFontVariants.bind( this );
        this.onClickCurrentFont = this.onClickCurrentFont.bind( this );
    }

    componentDidMount() {

    }

    componentDidUpdate( prevProps ) {
        if ( prevProps.filteredItems !== this.props.filteredItems ) {
            this.filter( this.state.filterValue );
        }
    }

    onChangeSearchInput( event ) {
        this.filter( event.target.value );
    }

    filter( filterValue = '' ) {

        if ( '' === filterValue ) {
            this.setState( {
                filterValue,
                filteredItems: [],
                showResults: false
            } );

            return;
        }

        const filteredItems = this.availableFonts.filter(
            ( { label, variations = [] } ) => {
                const normalizedLabel = label.toLowerCase();

                if ( normalizedLabel.startsWith( filterValue.toLowerCase() ) ) {
                    return true;
                }
            }
        );

        this.setState( {
            filterValue,
            filteredItems,
            showResults: true
        } );
    }

    onClickCurrentFont( event ) {
        const {
            currentFont,
        } = this.props;

        this.filter( currentFont.label );
    }

    onChangeFont( font ) {

        const {
            currentFont,
        } = this.props;

        if ( currentFont && currentFont.name === font.name ) {
            return;
        }

        this.props.onUpdateFont( font.name, { regular: 'regular' } );
    }

    onChangeFontVariants( fontVariants ) {
        this.props.onUpdateFont( this.props.currentFont.name, fontVariants );
    }

    render() {
        const {
            filteredItems,
            filterValue,
            showResults
        } = this.state;

        const {
            currentFont,
            currentFontVariants,
            displayType
        } = this.props;

        const hasItems             =  ! isEmpty( filteredItems );
        const onChangeFont         = this.onChangeFont;
        const onChangeFontVariants = this.onChangeFontVariants;
        const currentDisplayType   = getSetting( 'fontDisplayTypes' )[ displayType ];
        const fontVariants         = getSetting( 'fontVariationTypes' );
        const defaultFont          = getSetting( 'defaultFont' );
        const defaultFontData      = defaultFont ? getFont( defaultFont.name ) : {};

        return (
            <div className="sab-fonts-wrapper">
                <label className="fonts-wrapper-label">
                    <span className="inner-label">{ currentDisplayType.title }</span>
                    { currentFont &&
                        <span className="current-font" onClick={ this.onClickCurrentFont }>
                            { currentFont.label }
                        </span>
                    }
                    { ! currentFont && defaultFontData &&
                        <span className="current-font default-font">
                            { defaultFontData.label }
                        </span>
                    }
                </label>
                <div
                    className="sab-fonts-search"
                    onKeyPress={ stopKeyPropagation }
                >
                    <div className="font-list-wrapper">
                        <input
                            type="search"
                            placeholder={ _x( 'Search for a font', 'storeabill-core', 'storeabill' ) }
                            className="font-search"
                            autoFocus
                            value={filterValue}
                            onChange={ this.onChangeSearchInput }
                        />
                        <div
                            className="font-results"
                            tabIndex="0"
                            role="region"
                            aria-label={ _x( 'Available fonts', 'storeabill-core', 'storeabill' ) }
                        >
                            { showResults &&
                                <ul role="list" className="font-list">
                                    { filteredItems.map( ( item ) => {
                                        return (
                                            <li
                                                className={ classnames(
                                                    'font-family',
                                                    {
                                                        ['active']: currentFont && currentFont.name === item.name
                                                    }
                                                ) }
                                                onClick={ ( event ) => {
                                                    event.preventDefault();
                                                    onChangeFont( item );
                                                } }
                                                key={ item.name }
                                            >
                                                <span className="font-title">{ item.label }</span>

                                                { ! isEmpty( item.variants ) && currentFont && ( item.name === currentFont.name ) &&
                                                    <ul className="font-variants">
                                                        { Object.keys( fontVariants ).map( function( variant, index) {
                                                            const currentVariant = currentFontVariants.hasOwnProperty( variant ) ? currentFontVariants[ variant ] : 'regular';

                                                            return (
                                                                <li className="font-variant-select" key={ index }>
                                                                    <SelectControl
                                                                        label={ fontVariants[ variant ] }
                                                                        value={ currentVariant }
                                                                        options={
                                                                            item.variants.map( ( itemVariant ) => {
                                                                                return {
                                                                                    label: fontVariants.hasOwnProperty( itemVariant ) ? fontVariants[ itemVariant ] : itemVariant,
                                                                                    value: itemVariant
                                                                                };
                                                                            })
                                                                        }
                                                                        onChange={ ( currentVariant ) => { onChangeFontVariants( { [ variant ]: currentVariant } ) } }
                                                                    />
                                                                </li>
                                                            )
                                                        })}
                                                    </ul>
                                                }
                                            </li>
                                        );
                                    } ) }
                                </ul>
                            }
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}

const applyWithSelect = withSelect( ( select, { displayType } ) => {

    const { getEditedPostAttribute } = select( 'core/editor' );

    const meta      = getEditedPostAttribute( 'meta' );
    let fonts       = ! isEmpty( meta['_fonts'] ) ? meta['_fonts'] : {};
    let currentFont = fonts && fonts[ displayType ] ? fonts[ displayType ] : undefined;
    let trueFont    = currentFont ? getFont( currentFont['name'] ) : undefined;

    return {
        currentFont: trueFont,
        currentFontVariants: currentFont ? currentFont['variants'] : {}
    };
} );


const applyWithDispatch = withDispatch( ( dispatch, { displayType }, { select } ) => {

    const { editPost } = dispatch( 'core/editor' );
    const { getEditedPostAttribute } = select( 'core/editor' );

    const meta = getEditedPostAttribute( 'meta' );

    // Clone meta to prevent refs
    let fonts = meta['_fonts'] && ! isEmpty( meta['_fonts'] ) ? cloneDeep( meta['_fonts'] ) : {};

    return {
        onUpdateFont: ( fontName, fontVariants ) => {

            const currentFontName = fonts[ displayType ] ? fonts[ displayType ]['name'] : '';

            if ( fonts[ displayType ] && currentFontName !== fontName ) {
                fonts[ displayType ]['variants'] = {};
            }

            let newFontVariants = merge( fonts[ displayType ] ? fonts[ displayType ]['variants'] : {}, fontVariants );

            fonts[ displayType ] = {
                name: fontName,
                variants: newFontVariants
            };

            editPost( { meta: { '_fonts': fonts } } );
        },
    }
} );

export default compose(
    applyWithSelect,
    applyWithDispatch,
)( FontSelect );