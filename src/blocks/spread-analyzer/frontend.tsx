import React from 'react';
import ReactDOM from 'react-dom';

declare global {
    interface Window {
        spreadAnalyzerData: {
            strategy: string;
            risk: string;
        };
    }
}

const SpreadAnalyzerApp = () => {
    const data = window.spreadAnalyzerData || { strategy: 'N/A', risk: 'N/A' };

    return (
        <div>
            <h3>Spread Analyzer Frontend</h3>
            <p>Initial data received from backend:</p>
            <ul>
                <li><strong>Strategy:</strong> {data.strategy}</li>
                <li><strong>Risk:</strong> {data.risk}</li>
            </ul>
        </div>
    );
};

document.addEventListener('DOMContentLoaded', () => {
    const divsToUpdate = document.querySelectorAll('.wp-block-coco-stock-options-spread-analyzer');
    divsToUpdate.forEach(div => {
        ReactDOM.render(<SpreadAnalyzerApp />, div);
    });
});
