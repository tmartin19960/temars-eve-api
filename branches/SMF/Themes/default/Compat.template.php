<?php
// Version: 2.0 RC2; Compat

// Generate a strip of buttons, out of buttons.
function template_button_strip($button_strip, $direction = 'top', $strip_options = array())
{
	global $settings, $context, $txt, $scripturl;

	// Compatibility.
	if (!is_array($strip_options))
		$strip_options = array();

	// Right to left menu should be in reverse order.
	if ($context['right_to_left'])
		$button_strip = array_reverse($button_strip, true);

	// Create the buttons...
	$buttons = array();
	foreach ($button_strip as $key => $value)
		if (!isset($value['test']) || !empty($context[$value['test']]))
			$buttons[] = '<a href="' . $value['url'] . '"' . (isset($value['active']) ? ' class="active"' : '') . (isset($value['custom']) ? ' ' . $value['custom'] : '') . '><span>' . $txt[$value['text']] . '</span></a>';

	// No buttons? No button strip either.
	if (empty($buttons))
		return;

	// Make the last one, as easy as possible.
	$buttons[count($buttons) - 1] = str_replace('<span>', '<span class="last">', $buttons[count($buttons) - 1]);

	echo '
		<div class="buttonlist', $direction != 'top' ? '_bottom' : '', '"', (empty($buttons) ? ' style="display: none;"' : ''), (!empty($strip_options['id']) ? ' id="' . $strip_options['id'] . '"': ''), '>
			<ul class="reset clearfix">
				<li>', implode('</li><li>', $buttons), '</li>
			</ul>
		</div>';
}

?>