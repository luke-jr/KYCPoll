function save_answers() {
	var accept_checkbox = document.getElementById('accept_terms');
	if (!accept_checkbox.checked) {
		alert('Please accept the terms first');
		return;
	}
	accept_checkbox.disabled = true;
	var button = document.getElementById('save_button');
	button.value = 'Saving...';
	var form = document.getElementById('pollform');
	var to_disable = form.getElementsByTagName('input');
	for (var i = 0; i < to_disable.length; ++i) {
		to_disable[i].disabled = true;
	}
	form.submit();
}

function accept_terms_clicked() {
	var button = document.getElementById('save_button');
	var placeholder = document.getElementById('save_button_placeholder');
	if (button) {
		placeholder.removeChild(button);
	}
	var checkbox = document.getElementById('accept_terms');
	if (!checkbox.checked) {
		return;
	}
	button = document.createElement('input');
	button.id = 'save_button';
	button.type = "button";
	button.value = "Save";
	button.onclick = save_answers;
	placeholder.appendChild(button);
}
