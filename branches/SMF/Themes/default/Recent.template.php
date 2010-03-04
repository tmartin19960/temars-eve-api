<?php
// Version: 2.0 RC2; Recent

function template_main()
{
	global $context, $settings, $options, $txt, $scripturl;

	echo '
	<div id="recent" class="main_section">
		<div class="pagesection">
			<div>', $txt['pages'], ': ', $context['page_index'], '</div>
		</div>';

	foreach ($context['posts'] as $post)
	{
		// This is far from ideal, but oh well - create buttons for the post.
		$button_set = array();

		if ($post['can_delete'])
			$button_set['delete'] = array('text' => 'remove', 'image' => 'delete.gif', 'lang' => true, 'custom' => 'onclick="return confirm(\'' . $txt['remove_message'] . '?\');"', 'url' => $scripturl . '?action=deletemsg;msg=' . $post['id'] . ';topic=' . $post['topic'] . ';recent;' . $context['session_var'] . '=' . $context['session_id']);
		if ($post['can_reply'])
		{
			$button_set['reply'] = array('text' => 'reply', 'image' => 'reply_sm.gif', 'lang' => true, 'url' => $scripturl . '?action=post;topic=' . $post['topic'] . '.' . $post['start']);
			$button_set['quote'] = array('text' => 'reply_quote', 'image' => 'quote.gif', 'lang' => true, 'url' => $scripturl . '?action=post;topic=' . $post['topic'] . '.' . $post['start'] . ';quote=' . $post['id'] . ';' . $context['session_var'] . '=' . $context['session_id']);
		}
		if ($post['can_mark_notify'])
			$button_set['notify'] = array('text' => 'notify_replies', 'image' => 'notify_sm.gif', 'lang' => true, 'url' => $scripturl . '?action=notify;topic=' . $post['topic'] . '.' . $post['start']);

		echo '
		<div class="flow_hidden">
			<h3 class="catbg"><span class="left"></span>
				<span class="align_left">', $post['counter'], ' -&nbsp;</span>
				<span class="align_left">', $post['category']['link'], ' / ', $post['board']['link'], ' / <strong>', $post['link'], '</strong></span>
			</h3>
			<h4 class="titlebg"><span class="left"></span>
				<span class="align_left">', $txt['started_by'], ' ', $post['first_poster']['link'], ' - ', $txt['last_post'], ' ', $txt['by'], ' ', $post['poster']['link'], ' </span>
				<span class="align_right">', $txt['on'], ': ', $post['time'], '</span>
			</h4>
			<div class="windowbg2">
				<span class="topslice"><span></span></span>
				<div class="content">
					', $post['message'], '
				</div>
				<span class="botslice"><span></span></span>
			</div>';

		if (!empty($button_set))
			template_button_strip($button_set, 'right');

		echo '
			<br class="clear" />
		</div>';
	}

	echo '
		<div class="pagesection">
			<div class="align_left">', $txt['pages'], ': ', $context['page_index'], '</div>
		</div>
	</div>';
}

function template_unread()
{
	global $context, $settings, $options, $txt, $scripturl, $modSettings;

	echo '
	<div id="recent" class="main_content">';

	$showCheckboxes = !empty($options['display_quick_mod']) && $options['display_quick_mod'] == 1 && $settings['show_mark_read'];

	if ($showCheckboxes)
		echo '
		<form action="', $scripturl, '?action=quickmod" method="post" accept-charset="', $context['character_set'], '" name="quickModForm" id="quickModForm" style="margin: 0;">
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
			<input type="hidden" name="qaction" value="markread" />
			<input type="hidden" name="redirect_url" value="action=unread', (!empty($context['showing_all_topics']) ? ';all' : ''), $context['querystring_board_limits'], '" />';

	if ($settings['show_mark_read'])
	{
		// Generate the button strip.
		$mark_read = array(
			'markread' => array('text' => !empty($context['no_board_limits']) ? 'mark_as_read' : 'mark_read_short', 'image' => 'markread.gif', 'lang' => true, 'url' => $scripturl . '?action=markasread;sa=' . (!empty($context['no_board_limits']) ? 'all' : 'board' . $context['querystring_board_limits']) . ';' . $context['session_var'] . '=' . $context['session_id']),
		);

		if ($showCheckboxes)
			$mark_read['markselectread'] = array(
				'text' => 'quick_mod_markread',
				'image' => 'markselectedread.gif',
				'lang' => true,
				'url' => 'javascript:document.quickModForm.submit();',
			);
	}

	echo '
			<div class="pagesection">';

	if (!empty($mark_read) && !empty($settings['use_tabs']))
		template_button_strip($mark_read, 'right');

	echo '
				<div class="middletext pagelinks">', $txt['pages'], ': ', $context['page_index'], '</div>
			</div>';

		echo '
			<div class="tborder topic_table" id="unread">
				<table class="table_grid" cellspacing="0">
					<thead>
					<tr class="catbg">';

	if (!empty($context['topics']))
	{
		echo '
						<th scope="col" class="smalltext first_th" width="8%" colspan="2">&nbsp;</th>
						<th scope="col" class="smalltext">
							<a href="', $scripturl, '?action=unread', $context['showing_all_topics'] ? ';all' : '', $context['querystring_board_limits'], ';sort=subject', $context['sort_by'] == 'subject' && $context['sort_direction'] == 'up' ? ';desc' : '', '">', $txt['subject'], $context['sort_by'] == 'subject' ? ' <img src="' . $settings['images_url'] . '/sort_' . $context['sort_direction'] . '.gif" alt="" border="0" />' : '', '</a>
						</th>
						<th scope="col" class="smalltext" width="14%" align="center">
							<a href="', $scripturl, '?action=unread', $context['showing_all_topics'] ? ';all' : '', $context['querystring_board_limits'], ';sort=replies', $context['sort_by'] == 'replies' && $context['sort_direction'] == 'up' ? ';desc' : '', '">', $txt['replies'], $context['sort_by'] == 'replies' ? ' <img src="' . $settings['images_url'] . '/sort_' . $context['sort_direction'] . '.gif" alt="" border="0" />' : '', '</a>
						</th>';
		// Show a "select all" box for quick moderation?
		if ($showCheckboxes)
				echo '		
						<th scope="col" class="smalltext" width="22%">
							<a href="', $scripturl, '?action=unread', $context['showing_all_topics'] ? ';all' : '', $context['querystring_board_limits'], ';sort=last_post', $context['sort_by'] == 'last_post' && $context['sort_direction'] == 'up' ? ';desc' : '', '">', $txt['last_post'], $context['sort_by'] == 'last_post' ? ' <img src="' . $settings['images_url'] . '/sort_' . $context['sort_direction'] . '.gif" alt="" border="0" />' : '', '</a>
						</th>
						<th class="smalltext last_th">
							<input type="checkbox" onclick="invertAll(this, this.form, \'topics[]\');" class="input_check" />
						</th>';						
						else
				echo '			
						<th scope="col" class="smalltext last_th" width="22%">
							<a href="', $scripturl, '?action=unread', $context['showing_all_topics'] ? ';all' : '', $context['querystring_board_limits'], ';sort=last_post', $context['sort_by'] == 'last_post' && $context['sort_direction'] == 'up' ? ';desc' : '', '">', $txt['last_post'], $context['sort_by'] == 'last_post' ? ' <img src="' . $settings['images_url'] . '/sort_' . $context['sort_direction'] . '.gif" alt="" border="0" />' : '', '</a>
						</th>';
	}
	else
		echo '
						<th scope="col" class="smalltext" width="100%" colspan="4">', $context['showing_all_topics'] ? $txt['msg_alert_none'] : $txt['unread_topics_visit_none'], '</th>';

	echo '
					</tr>
					</thead>
					<tbody>';

	foreach ($context['topics'] as $topic)
	{
		// Calculate the color class of the topic.
		$color_class = '';
		if (strpos($topic['class'], 'sticky') !== false)
			$color_class = 'stickybg';
		if (strpos($topic['class'], 'locked') !== false)
			$color_class .= 'lockedbg';

		$color_class2 = !empty($color_class) ? $color_class . '2' : '';

		echo '
					<tr>
						<td class="', $color_class, ' icon1 windowbg">
							<img src="', $settings['images_url'], '/topic/', $topic['class'], '.gif" alt="" />
						</td>
						<td class="', $color_class, ' icon2 windowbg">
							<img src="', $topic['first_post']['icon_url'], '" alt="" />
						</td>
						<td class="subject ', $color_class2, ' windowbg2">
							<div>
							', $topic['is_sticky'] ? '<strong>' : '', '<span id="msg_' . $topic['first_post']['id'] . '">', $topic['first_post']['link'], '</span>', $topic['is_sticky'] ? '</strong>' : '' ,'
								<a href="', $topic['new_href'], '" id="newicon', $topic['first_post']['id'], '"><img src="', $settings['lang_images_url'], '/new.gif" alt="', $txt['new'], '" /></a>
								<p>
									', $txt['started_by'], ' <strong>', $topic['first_post']['member']['link'], '</strong>
									', $txt['in'], ' <em>', $topic['board']['link'], '</em>
									<small id="pages', $topic['first_post']['id'], '">', $topic['pages'], '</small>
								</p>
							</div>
						</td>
						<td class="', $color_class, ' stats windowbg">
							', $topic['replies'], ' ', $txt['replies'], '
							<br />
							', $topic['views'], ' ', $txt['views'], '
						</td>
						<td class="', $color_class2, ' lastpost windowbg2">
							<a href="', $topic['last_post']['href'], '"><img src="', $settings['images_url'], '/icons/last_post.gif" alt="', $txt['last_post'], '" title="', $txt['last_post'], '" style="float: right;" /></a>
							', $topic['last_post']['time'], '<br />
							', $txt['by'], ' ', $topic['last_post']['member']['link'], '
						</td>';
			if ($showCheckboxes)
				echo '
						<td class="windowbg2" valign="middle" align="center">
							<input type="checkbox" name="topics[]" value="', $topic['id'], '" class="input_check" />
						</td>';

		echo '
					</tr>';

	}

	if (!empty($context['topics']) && !$context['showing_all_topics'])
		$mark_read['readall'] = array('text' => 'unread_topics_all', 'image' => 'markreadall.gif', 'lang' => true, 'url' => $scripturl . '?action=unread;all' . $context['querystring_board_limits'], 'active' => true);

	if (empty($settings['use_tabs']) && !empty($mark_read))
		echo '
					<tr>
						<td class="catbg" colspan="', $showCheckboxes ? '6' : '5', '" align="right">
							', template_button_strip($mark_read, 'top'), '
						</td>
					</tr>';

	if (empty($context['topics']))
		echo '
					<tr style="display: none;"><td></td></tr>';

	echo '
				</tbody>
				</table>
			</div>
			<div class="pagesection" id="readbuttons">';

	if (!empty($settings['use_tabs']) && !empty($mark_read))
		template_button_strip($mark_read, 'right');

	echo '
				<div class="middletext pagelinks">', $txt['pages'], ': ', $context['page_index'], '</div>
			</div>
	';

	if ($showCheckboxes)
		echo '
		</form>';

	echo '
		<div class="description flow_auto" id="topic_icons">
			<p class="smalltext align_left">
				', !empty($modSettings['enableParticipation']) ? '
				<img src="' . $settings['images_url'] . '/topic/my_normal_post.gif" alt="" align="middle" /> ' . $txt['participation_caption'] . '<br />' : '', '
				<img src="', $settings['images_url'], '/topic/normal_post.gif" alt="" align="middle" /> ', $txt['normal_topic'], '<br />
				<img src="', $settings['images_url'], '/topic/hot_post.gif" alt="" align="middle" /> ', sprintf($txt['hot_topics'], $modSettings['hotTopicPosts']), '<br />
				<img src="', $settings['images_url'], '/topic/veryhot_post.gif" alt="" align="middle" /> ', sprintf($txt['very_hot_topics'], $modSettings['hotTopicVeryPosts']), '
			</p>
			<p class="smalltext align_left">
				<img src="', $settings['images_url'], '/icons/quick_lock.gif" alt="" align="middle" /> ', $txt['locked_topic'], '<br />', ($modSettings['enableStickyTopics'] == '1' ? '
				<img src="' . $settings['images_url'] . '/icons/quick_sticky.gif" alt="" align="middle" /> ' . $txt['sticky_topic'] . '<br />' : ''), ($modSettings['pollMode'] == '1' ? '
				<img src="' . $settings['images_url'] . '/topic/normal_poll.gif" alt="" align="middle" /> ' . $txt['poll'] : ''), '
			</p>
		</div>
	</div>';
}

function template_replies()
{
	global $context, $settings, $options, $txt, $scripturl, $modSettings;

	echo '
	<div id="recent">';

	$showCheckboxes = !empty($options['display_quick_mod']) && $options['display_quick_mod'] == 1 && $settings['show_mark_read'];

	if ($showCheckboxes)
		echo '
		<form action="', $scripturl, '?action=quickmod" method="post" accept-charset="', $context['character_set'], '" name="quickModForm" id="quickModForm" style="margin: 0;">
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
			<input type="hidden" name="qaction" value="markread" />
			<input type="hidden" name="redirect_url" value="action=unreadreplies', (!empty($context['showing_all_topics']) ? ';all' : ''), $context['querystring_board_limits'], '" />';

	if (isset($context['topics_to_mark']) && !empty($settings['show_mark_read']))
	{
		// Generate the button strip.
		$mark_read = array(
			'markread' => array('text' => 'mark_as_read', 'image' => 'markread.gif', 'lang' => true, 'url' => $scripturl . '?action=markasread;sa=unreadreplies;topics=' . $context['topics_to_mark'] . ';' . $context['session_var'] . '=' . $context['session_id']),
		);

		if ($showCheckboxes)
			$mark_read['markselectread'] = array(
				'text' => 'quick_mod_markread',
				'image' => 'markselectedread.gif',
				'lang' => true,
				'url' => 'javascript:document.quickModForm.submit();',
			);
	}

	echo '
			<div class="pagesection">';

	if (!empty($mark_read) && !empty($settings['use_tabs']))
		template_button_strip($mark_read, 'right');

	echo '
				<div class="pagelinks">', $txt['pages'], ': ', $context['page_index'], '</div>
			</div>';

		echo '
			<div class="tborder topic_table" id="unreadreplies">
				<table class="table_grid" cellspacing="0">
					<thead>
					<tr class="catbg">';

	if (!empty($context['topics']))
	{
		echo '
						<th scope="col" class="smalltext first_th" width="8%" colspan="2">&nbsp;</th>
						<th scope="col" class="smalltext">
							<a href="', $scripturl, '?action=unreadreplies', $context['querystring_board_limits'], ';sort=subject', $context['sort_by'] == 'subject' && $context['sort_direction'] == 'up' ? ';desc' : '', '">', $txt['subject'], $context['sort_by'] == 'subject' ? ' <img src="' . $settings['images_url'] . '/sort_' . $context['sort_direction'] . '.gif" alt="" border="0" />' : '', '</a>
						</th>
						<th scope="col" class="smalltext" width="14%" align="center">
							<a href="', $scripturl, '?action=unreadreplies', $context['querystring_board_limits'], ';sort=replies', $context['sort_by'] == 'replies' && $context['sort_direction'] == 'up' ? ';desc' : '', '">', $txt['replies'], $context['sort_by'] == 'replies' ? ' <img src="' . $settings['images_url'] . '/sort_' . $context['sort_direction'] . '.gif" alt="" border="0" />' : '', '</a>
						</th>';
		// Show a "select all" box for quick moderation?
		if ($showCheckboxes)
				echo '		
						<th scope="col" class="smalltext" width="22%">
							<a href="', $scripturl, '?action=unreadreplies', $context['querystring_board_limits'], ';sort=last_post', $context['sort_by'] == 'last_post' && $context['sort_direction'] == 'up' ? ';desc' : '', '">', $txt['last_post'], $context['sort_by'] == 'last_post' ? ' <img src="' . $settings['images_url'] . '/sort_' . $context['sort_direction'] . '.gif" alt="" border="0" />' : '', '</a>
						</th>
						<th class="smalltext last_th">
							<input type="checkbox" onclick="invertAll(this, this.form, \'topics[]\');" class="input_check" />
						</th>';						
						else
				echo '			
						<th scope="col" class="smalltext last_th" width="22%">
							<a href="', $scripturl, '?action=unreadreplies', $context['querystring_board_limits'], ';sort=last_post', $context['sort_by'] == 'last_post' && $context['sort_direction'] == 'up' ? ';desc' : '', '">', $txt['last_post'], $context['sort_by'] == 'last_post' ? ' <img src="' . $settings['images_url'] . '/sort_' . $context['sort_direction'] . '.gif" alt="" border="0" />' : '', '</a>
						</th>';
	}
	else
		echo '
						<th scope="col" class="smalltext" width="100%" colspan="4">', $context['showing_all_topics'] ? $txt['msg_alert_none'] : $txt['unread_topics_visit_none'], '</th>';

	echo '
					</tr>
					</thead>
					<tbody>';

	foreach ($context['topics'] as $topic)
	{
		// Calculate the color class of the topic.
		$color_class = '';
		if (strpos($topic['class'], 'sticky') !== false)
			$color_class = 'stickybg';
		if (strpos($topic['class'], 'locked') !== false)
			$color_class .= 'lockedbg';

		$color_class2 = !empty($color_class) ? $color_class . '2' : '';

		echo '
					<tr>
						<td class="', $color_class, ' icon1 windowbg">
							<img src="', $settings['images_url'], '/topic/', $topic['class'], '.gif" alt="" />
						</td>
						<td class="', $color_class, ' icon2 windowbg">
							<img src="', $topic['first_post']['icon_url'], '" alt="" />
						</td>
						<td class="subject ', $color_class2, ' windowbg2">
							<div>
								', $topic['is_sticky'] ? '<strong>' : '', '<span id="msg_' . $topic['first_post']['id'] . '">', $topic['first_post']['link'], '</span>', $topic['is_sticky'] ? '</strong>' : '', '
								<a href="', $topic['new_href'], '" id="newicon', $topic['first_post']['id'], '"><img src="', $settings['lang_images_url'], '/new.gif" alt="', $txt['new'], '" /></a>
								<p>
									', $txt['started_by'], ' <strong>', $topic['first_post']['member']['link'], '</strong>
									', $txt['in'], ' <em>', $topic['board']['link'], '</em>
									<small id="pages', $topic['first_post']['id'], '">', $topic['pages'], '</small>
								</p>
							</div>
						</td>
						<td class="', $color_class, ' stats windowbg">
							', $topic['replies'], ' ', $txt['replies'], '
							<br />
							', $topic['views'], ' ', $txt['views'], '
						</td>
						<td class="', $color_class2, ' lastpost windowbg2">
							<a href="', $topic['last_post']['href'], '"><img src="', $settings['images_url'], '/icons/last_post.gif" alt="', $txt['last_post'], '" title="', $txt['last_post'], '" style="float: right;" /></a>
							', $topic['last_post']['time'], '<br />
							', $txt['by'], ' ', $topic['last_post']['member']['link'], '
						</td>';
			if ($showCheckboxes)
				echo '
						<td class="windowbg2" valign="middle" align="center">
							<input type="checkbox" name="topics[]" value="', $topic['id'], '" class="input_check" />
						</td>';

		echo '
					</tr>';

	}
	if (empty($settings['use_tabs']) && !empty($mark_read))
		echo '
					<tr>
						<td class="catbg" colspan="', $showCheckboxes ? '6' : '5', '" align="right">
							', template_button_strip($mark_read, 'top'), '
						</td>
					</tr>';

	if (empty($context['topics']))
		echo '
					<tr style="display: none;"><td></td></tr>';

	echo '
					</tbody>
				</table>
			</div>
			<div class="pagesection">';

	if (!empty($settings['use_tabs']) && !empty($mark_read))
		template_button_strip($mark_read, 'right');

	echo '
				<div class="middletext pagelinks">', $txt['pages'], ': ', $context['page_index'], '</div>
			</div>';

	if ($showCheckboxes)
		echo '
		</form>';

	echo '
		<div class="description flow_auto" id="topic_icons">
			<p class="smalltext align_left">
				', !empty($modSettings['enableParticipation']) ? '
				<img src="' . $settings['images_url'] . '/topic/my_normal_post.gif" alt="" align="middle" /> ' . $txt['participation_caption'] . '<br />' : '', '
				<img src="', $settings['images_url'], '/topic/normal_post.gif" alt="" align="middle" /> ', $txt['normal_topic'], '<br />
				<img src="', $settings['images_url'], '/topic/hot_post.gif" alt="" align="middle" /> ', sprintf($txt['hot_topics'], $modSettings['hotTopicPosts']), '<br />
				<img src="', $settings['images_url'], '/topic/veryhot_post.gif" alt="" align="middle" /> ', sprintf($txt['very_hot_topics'], $modSettings['hotTopicVeryPosts']), '
			</p>
			<p class="smalltext align_left">
				<img src="', $settings['images_url'], '/icons/quick_lock.gif" alt="" align="middle" /> ', $txt['locked_topic'], '<br />', ($modSettings['enableStickyTopics'] == '1' ? '
				<img src="' . $settings['images_url'] . '/icons/quick_sticky.gif" alt="" align="middle" /> ' . $txt['sticky_topic'] . '<br />' : '') . ($modSettings['pollMode'] == '1' ? '
				<img src="' . $settings['images_url'] . '/topic/normal_poll.gif" alt="" align="middle" /> ' . $txt['poll'] : '') . '
			</p>
		</div>
	</div>';
}

?>