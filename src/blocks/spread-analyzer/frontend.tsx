import React from 'react';
import ReactDOM from 'react-dom';
import SpreadAnalyzerApp from './react-app/SpreadAnalyzer';

document.addEventListener('DOMContentLoaded', () => {

    // Check if we're on a single stock post
		// not very elegant way, but works most of cases.
    let currentPostId = null;
    if (document.body.classList.contains('single') &&
        document.body.classList.contains('single-stock')) {
        currentPostId = document.body.classList
            .toString()
            .match(/postid-(\d+)/)?.[1] || null;
    }


    const divsToUpdate = document.querySelectorAll('.wp-block-coco-stock-options-spread-analyzer');
    divsToUpdate.forEach(div => {
        const side = div.getAttribute('data-side') || 'PUT';
        let stockId = div.getAttribute('data-stock-id') ?? null;
				if ( ! Number(stockId) ) {
					stockId = currentPostId?.toString() ?? null;
				}
				if ( stockId ) {
					ReactDOM.render(<SpreadAnalyzerApp side={side as 'PUT' | 'CALL'} stockId={parseInt(stockId.toString())} />, div);
				}


    });
});

