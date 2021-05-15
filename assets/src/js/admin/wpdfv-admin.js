document.addEventListener( 'DOMContentLoaded', () => {
	const saveBtn = document.getElementById( 'wpdfv-save-settings' );
	const formElement = document.getElementById( 'wpdfv-admin-settings-form' );

	if ( saveBtn ) {
		saveBtn.addEventListener( 'click', ( e ) => {
			e.preventDefault();

			// Disable the save button for unnecesssary clicks.
			saveBtn.setAttribute( 'disabled', 'disabled' );

			// Change Save button label to `Saving...` for more clarity.
			saveBtn.value = saveBtn.getAttribute( 'data-processing-text' );

			const formData = new FormData( formElement );
			formData.append( 'action', 'wpdfv_save_admin_settings' );

			fetch(
				ajaxurl,
				{
					method: 'POST',
					body: formData,
				}
			).then( response => {
				if ( 200 === response.status ) {
					return response.json();
				}

				return false;
			} ).then( response => {
				if ( response ) {
					saveBtn.value = saveBtn.getAttribute( 'data-saved-text' );
				}

				setTimeout( () => {
					saveBtn.removeAttribute( 'disabled' );
					saveBtn.value = saveBtn.getAttribute( 'data-default-text' );
				}, 1000 );
			} );
		} );
	}
} );
