import React from 'react';
import ReactDOM from 'react-dom';
import SpreadAnalyzerApp from './react-app/SpreadAnalyzer';

document.addEventListener('DOMContentLoaded', () => {
    const divsToUpdate = document.querySelectorAll('.wp-block-coco-stock-options-spread-analyzer');
    divsToUpdate.forEach(div => {
        const side = div.getAttribute('data-side');
        const stockId = div.getAttribute('data-stock-id');
        ReactDOM.render(<SpreadAnalyzerApp side={side} stockId={stockId} />, div);
    });
});
