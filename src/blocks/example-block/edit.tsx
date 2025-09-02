import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import './editor.css';

export default function Edit() {
    return (
        <p { ...useBlockProps() }>
            { __( 'Example Block – hello from the editor!', 'coco-stock-options' ) }
        </p>
    );
}
