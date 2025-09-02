import { useBlockProps } from '@wordpress/block-editor';

export default function save({ attributes }) {
    const { side, stockId } = attributes;
    return <div {...useBlockProps.save({ 'data-side': side, 'data-stock-id': stockId })} />;
}
