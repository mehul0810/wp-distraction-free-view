/**
 * WP Distraction Free View - Frontend
 * React-based implementation using @wordpress packages
 */
/* global wpdfv, Element */
import {createRoot, useState, useEffect} from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import domReady from '@wordpress/dom-ready';
import './../../css/frontend/wpdfv.scss';

const DistractionFreeOverlay = () => {
	const [isVisible, setIsVisible] = useState(false);
	const [content, setContent] = useState('');

	// Fetch post content
	const fetchPostContent = async (postId) => {
		try {
			const response = await apiFetch({
				url: `${wpdfv.ajaxurl}?action=display_post_details&id=${postId}&nonce=${wpdfv.nonce}`,
				method: 'GET',
			});

			setContent(response);
			setIsVisible(true);
			document.body.style.overflowY = 'hidden';
		} catch (error) {
			// eslint-disable-next-line no-console
			console.error('Error fetching post:', error);
		}
	};

	// Close overlay
	const closeOverlay = () => {
		setIsVisible(false);
		document.body.style.overflowY = 'scroll';
	};

	// Toggle fullscreen
	const toggleFullscreen = () => {
		if (
			!document.fullscreenElement &&
			!document.mozFullScreenElement &&
			!document.webkitFullscreenElement &&
			!document.msFullscreenElement
		) {
			// Enter fullscreen
			if (document.documentElement.requestFullscreen) {
				document.documentElement.requestFullscreen();
			} else if (document.documentElement.msRequestFullscreen) {
				document.documentElement.msRequestFullscreen();
			} else if (document.documentElement.mozRequestFullScreen) {
				document.documentElement.mozRequestFullScreen();
			} else if (document.documentElement.webkitRequestFullscreen) {
				document.documentElement.webkitRequestFullscreen();
			}

			// Hide close button in fullscreen
			const closeBtn = document.querySelector('.wpdfv-overlay-close');
			if (closeBtn) {
				closeBtn.style.display = 'none';
			}
		} else {
			// Exit fullscreen
			if (document.exitFullscreen) {
				document.exitFullscreen();
			} else if (document.msExitFullscreen) {
				document.msExitFullscreen();
			} else if (document.mozCancelFullScreen) {
				document.mozCancelFullScreen();
			} else if (document.webkitExitFullscreen) {
				document.webkitExitFullscreen();
			}

			// Show close button when exiting fullscreen
			const closeBtn = document.querySelector('.wpdfv-overlay-close');
			if (closeBtn) {
				closeBtn.style.display = 'block';
			}
		}
	};

	// Handle print
	const handlePrint = () => {
		const printWindow = window.open('', '', 'width=800,height=600');
		if (printWindow) {
			printWindow.document.write(content);
			printWindow.document.close();
			printWindow.print();
			printWindow.close();
		}
	};

	// Set up event listeners after mount
	useEffect(() => {
		// Add event listener for fullscreen button clicks
		const fullscreenBtn = document.querySelector('.wpdfv-fullscreen-container .wpdfv-fullscreen-btn');
		const handleFullscreenClick = (e) => {
			e.preventDefault();
			const postId = e.currentTarget.dataset.postId;
			if (postId) {
				fetchPostContent(postId);
			}
		};

		if (fullscreenBtn) {
			fullscreenBtn.addEventListener('click', handleFullscreenClick);
		}

		// Cleanup
		return () => {
			if (fullscreenBtn) {
				fullscreenBtn.removeEventListener('click', handleFullscreenClick);
			}
		};
	}, []);

	// Render overlay
	return (
		<div className="wpdfv-fullscreen-overlay-container" style={{display: isVisible ? 'block' : 'none'}}>
			<div className="wpdfv-fullscreen-overlay-header">
				<div className="wpdfv-actions">
					<button
						type="button"
						className="btn btn-primary wpdfv-overlay-print wpdfv-overlay-btn"
						onClick={handlePrint}
					>
						<img
							className="wpdfv-icon"
							src={`${wpdfv.pluginUrl}assets/dist/images/print.svg`}
							alt="Print"
						/>
					</button>
					<button
						type="button"
						className="wpdfv-dual-fullscreen-btn wpdfv-overlay-btn"
						onClick={toggleFullscreen}
					>
						<img
							className="wpdfv-icon"
							src={`${wpdfv.pluginUrl}assets/dist/images/fullscreen.svg`}
							alt="Fullscreen"
						/>
					</button>
					<button type="button" className="wpdfv-overlay-close wpdfv-overlay-btn" onClick={closeOverlay}>
						<img
							className="wpdfv-icon"
							src={`${wpdfv.pluginUrl}assets/dist/images/close.svg`}
							alt="Close"
						/>
					</button>
				</div>
			</div>
			<div className="wpdfv-overlay-wrap" id="wpdfv-print" dangerouslySetInnerHTML={{__html: content}} />
		</div>
	);
};

// Initialize on DOM ready
domReady(() => {
	// Create a container for the React app if it doesn't exist
	let container = document.querySelector('#wpdfv-react-root');

	if (!container) {
		container = document.createElement('div');
		container.id = 'wpdfv-react-root';
		document.body.appendChild(container);
	}

	// Render the React component
	const root = createRoot(container);
	root.render(<DistractionFreeOverlay />);
});
