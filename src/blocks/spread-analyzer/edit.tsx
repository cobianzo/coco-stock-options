import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export default function Edit() {
    return (
        <p {...useBlockProps()}>
            {__('Spread Analyzer Block - Editor View', 'coco-stock-options')}
        </p>
    );
}
