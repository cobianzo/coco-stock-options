import React from 'react';
import Draggable from 'react-draggable';
import './Popup.css';

interface PopupProps {
	isOpen: boolean;
	onClose: () => void;
	title?: string;
	children: React.ReactNode;
}

const Popup: React.FC<PopupProps> = ({ isOpen, onClose, title, children }) => {
	if (!isOpen) {
		return null;
	}

	return (
		<Draggable handle=".popup-header">
			<div className="popup-content">
				<div className="popup-header">
					<h4 className="popup-title">{title || 'Popup'}</h4>
					<button onClick={onClose} className="popup-close-btn">
						&times;
					</button>
				</div>
				<div className="popup-body">{children}</div>
			</div>
		</Draggable>
	);
};

export default Popup;
