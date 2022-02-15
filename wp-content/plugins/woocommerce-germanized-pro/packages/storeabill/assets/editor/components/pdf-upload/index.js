import { withSelect, withDispatch } from '@wordpress/data';
import {Button, DropZone, ResponsiveWrapper, Spinner} from '@wordpress/components';
import { Component } from '@wordpress/element';
import {__, _x} from '@wordpress/i18n';
import { compose } from '@wordpress/compose';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { get } from 'lodash';

/**
 * Internal dependencies
 */
import './editor.scss';

function PDFUpload( {
    pdfAttachmentId,
    onUpdateAttachment,
    onDropAttachment,
    onRemoveAttachment,
    metaKey,
    pdfAttachment,
} ) {

    const getAttachmentThumb = ( image, sizeSlug, attribute ) => {
        return get( image, [ 'media_details', 'sizes', sizeSlug, attribute ] );
    };

    const instructions = (
        <p>
            { _x( 'To edit the PDF file, you need permission to upload media.', 'storeabill-core', 'storeabill' ) }
        </p>
    );

    let previewThumb = false;
    let thumbWidth   = 0;
    let thumbHeight  = 0;

    if ( pdfAttachment ) {
        previewThumb = getAttachmentThumb( pdfAttachment, 'full', 'source_url' );
        thumbWidth   = getAttachmentThumb( pdfAttachment, 'full', 'width' );
        thumbHeight  = getAttachmentThumb( pdfAttachment, 'full', 'height' );
    }

    return (
        <div className="editor-document-template-pdf">
            <MediaUploadCheck fallback={ instructions }>
                <MediaUpload
                    title={_x('PDF Attachment', 'storeabill-core', 'storeabill')}
                    onSelect={ onUpdateAttachment }
                    allowedTypes={['application/pdf']}
                    modalClass={
                        ! pdfAttachmentId
                            ? 'editor-post-featured-image__media-modal'
                            : 'editor-post-featured-image__media-modal'
                    }
                    render={({open}) => (
                        <div className="editor-post-featured-image__container">
                            <Button
                                className={
                                    ! pdfAttachmentId
                                        ? 'editor-post-featured-image__toggle'
                                        : 'editor-post-featured-image__preview'
                                }
                                onClick={ open }
                                aria-label={
                                    ! pdfAttachmentId
                                        ? null
                                        : _x( 'Edit or update the PDF file', 'storeabill-core', 'storeabill' )
                                }
                            >
                                { !! pdfAttachmentId && pdfAttachment && previewThumb && (
                                    <ResponsiveWrapper
                                        naturalWidth={ thumbWidth }
                                        naturalHeight={ thumbHeight }
                                        isInline
                                    >
                                        <img
                                            src={ previewThumb }
                                            alt=""
                                        />
                                    </ResponsiveWrapper>
                                ) }
                                { !! pdfAttachmentId && pdfAttachment && ! previewThumb && (
                                    <span className="pdf-image-placeholder">{ pdfAttachment.title.rendered }</span>
                                ) }
                                { !! pdfAttachmentId && ! pdfAttachment && (
                                    <Spinner />
                                ) }
                                { ! pdfAttachmentId &&
                                ( _x( 'Set PDF background', 'storeabill-core', 'storeabill' ) ) }
                            </Button>
                            <DropZone onFilesDrop={ onDropAttachment } />
                        </div>
                    )}
                    value={ pdfAttachmentId }
                />
            </MediaUploadCheck>
            { !! pdfAttachmentId && pdfAttachment &&
            <MediaUploadCheck>
                <MediaUpload
                    title={_x('PDF Attachment', 'storeabill-core', 'storeabill')}
                    onSelect={ onUpdateAttachment }
                    allowedTypes={['application/pdf']}
                    value={ pdfAttachmentId }
                    render={({open}) => (
                        <Button onClick={open} isDefault isLarge>
                            {_x('Replace PDF template', 'storeabill-core', 'storeabill')}
                        </Button>
                    )}
                />
            </MediaUploadCheck>
            }
            { !! pdfAttachmentId &&
            <MediaUploadCheck>
                <Button onClick={ onRemoveAttachment } isLink isDestructive>
                    {_x('Remove', 'storeabill-core', 'storeabill')}
                </Button>
            </MediaUploadCheck>
            }
        </div>
    );
}

const applyWithSelect = withSelect( ( select, { metaKey } ) => {

    const { getMedia } = select( 'core' );
    const { getEditedPostAttribute } = select( 'core/editor' );

    const meta = getEditedPostAttribute( 'meta' );
    const pdfAttachmentId = meta[ metaKey ];

    const attachment = pdfAttachmentId ? getMedia( pdfAttachmentId ) : null;

    return {
        pdfAttachment: attachment,
        pdfAttachmentId: pdfAttachmentId ? pdfAttachmentId : undefined,
    };
} );


const applyWithDispatch = withDispatch( ( dispatch, { metaKey }, { select } ) => {

    const { editPost } = dispatch( 'core/editor' );

    return {
        onUpdateAttachment: ( attachment ) => {
            let props = {};
            props[ metaKey ] = attachment.id;

            editPost( { meta: props } );
        },

        onDropAttachment: ( filesList ) => {
            select( 'core/block-editor' )
                .getSettings()
                .mediaUpload( {
                    allowedTypes: [ 'application/pdf' ],
                    filesList,
                    onFileChange( [ attachment ] ) {
                        let props = {};
                        props[ metaKey ] = attachment.id;

                        if ( attachment.id ) {
                            editPost( { meta: props } );
                        }
                    },
                    onError( message ) {
                        noticeOperations.removeAllNotices();
                        noticeOperations.createErrorNotice( message );
                    },
                } );
        },

        onRemoveAttachment() {
            let props = {};
            props[ metaKey ] = 0;

            editPost( { meta: props } );
        },
    }
} );

export default compose(
    applyWithSelect,
    applyWithDispatch,
)( PDFUpload );