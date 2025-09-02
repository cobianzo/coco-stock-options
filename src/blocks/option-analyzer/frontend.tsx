import React from 'react';
import ReactDOM from 'react-dom';

declare global {
    interface Window {
        optionAnalyzerData: {
            symbol: string;
            someValue: number;
        };
    }
}

const App = () => {
    const data = window.optionAnalyzerData || { symbol: 'N/A', someValue: 0 };

    return (
        <div>
            <h3>Option Analyzer Frontend</h3>
            <p>Initial data received from backend:</p>
            <ul>
                <li><strong>Stock Symbol:</strong> {data.symbol}</li>
                <li><strong>Some Value:</strong> {data.someValue}</li>
            </ul>
        </div>
    );
};

document.addEventListener('DOMContentLoaded', () => {
    const divsToUpdate = document.querySelectorAll('.wp-block-coco-stock-options-option-analyzer');
    divsToUpdate.forEach(div => {
        ReactDOM.render(<App />, div);
    });
});
