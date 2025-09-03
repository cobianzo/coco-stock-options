import React from 'react'
import { WPAllOptionsData } from 'src/types/types'

function ReloadData({
	optionsData,
	stockId,
	side
}: {
	optionsData: WPAllOptionsData,
	stockId: number,
	side: 'put' | 'call'
}) {


	const handleReloadData = function() {
		alert('we do a fetch');
	}

	return (
<button
    className="reload-button"
    onClick={handleReloadData}
    title="Reload Data"
>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        width="24"
        height="24"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
        strokeLinejoin="round"
    >
        <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.3"/>
    </svg>
</button>
	)
}

export default ReloadData