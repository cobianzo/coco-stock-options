import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

export default function Edit({ attributes, setAttributes }) {
    const { side, stockId } = attributes;

    const { stocks, hasResolved } = useSelect((select) => {
        const selector = select('core');
        return {
            stocks: selector.getEntityRecords('postType', 'stock', { per_page: -1 }),
            hasResolved: selector.hasFinishedResolution('getEntityRecords', ['postType', 'stock', { per_page: -1 }]),
        };
    }, []);

    const stockOptions = [
        { label: __('Auto', 'coco-stock-options'), value: 0 },
        ...(stocks || []).map(stock => ({ label: stock.title.rendered, value: stock.id }))
    ];

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Settings', 'coco-stock-options')}>
                    <SelectControl
                        label={__('Side', 'coco-stock-options')}
                        value={side}
                        options={[
                            { label: 'PUT', value: 'PUT' },
                            { label: 'CALL', value: 'CALL' },
                        ]}
                        onChange={(newSide) => setAttributes({ side: newSide })}
                    />
                    <SelectControl
                        label={__('Stock', 'coco-stock-options')}
                        value={stockId}
                        options={stockOptions}
                        onChange={(newStockId) => setAttributes({ stockId: parseInt(newStockId, 10) })}
                    />
                    {!hasResolved && <Spinner />}
                </PanelBody>
            </InspectorControls>
            <p {...useBlockProps()}>
                {__('Spread Analyzer Block - Selected Side:', 'coco-stock-options')} {side}
                <br />
                {__('Selected Stock ID:', 'coco-stock-options')} {stockId}
            </p>
        </>
    );
}
