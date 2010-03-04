<?php
// Version: 2.0 RC2; ManageMail

function template_browse()
{
	global $context, $settings, $options, $scripturl, $txt;

	echo '
	<div id="manage_mail">
		<h3 class="catbg"><span class="left"></span>
			', $txt['mailqueue_stats'], '
		</h3>
		<div class="windowbg">
			<span class="topslice"><span></span></span>
				<div class="content">
					<dl class="settings">
						<dt><strong>', $txt['mailqueue_size'], '</strong></dt>
						<dd>', $context['mail_queue_size'], '</dd>
						<dt><strong>', $txt['mailqueue_oldest'], '</strong></dt>
						<dd>', $context['oldest_mail'], '</dd>
					</dl>
				</div>
			<span class="botslice"><span></span></span>
		</div>';

	template_show_list('mail_queue');

	echo'
	</div>
	<br class="clear" />';
}

?>