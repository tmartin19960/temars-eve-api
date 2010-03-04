<?php
// Version: 2.0 RC2; SplitTopics

function template_ask()
{
	global $context, $settings, $options, $txt, $scripturl;

	echo '
	<div id="split_topics">
		<form action="', $scripturl, '?action=splittopics;sa=execute;topic=', $context['current_topic'], '.0" method="post" accept-charset="', $context['character_set'], '">
			<input type="hidden" name="at" value="', $context['message']['id'], '" />
			<h3 class="catbg">
				<span class="left"></span>
				', $txt['split'], '
			</h3>
			<div class="windowbg">
				<span class="topslice"><span></span></span>
				<div class="content">
					<p class="split_topics">
						<strong><label for="subname">', $txt['subject_new_topic'], '</label>:</strong>
						<input type="text" name="subname" id="subname" value="', $context['message']['subject'], '" size="25" class="input_text" />
					</p>
					<ul class="reset split_topics">
						<li>
							<input type="radio" id="onlythis" name="step2" value="onlythis" checked="checked" class="input_radio" /> <label for="onlythis">', $txt['split_this_post'], '</label>
						</li>
						<li>
							<input type="radio" id="afterthis" name="step2" value="afterthis" class="input_radio" /> <label for="afterthis">', $txt['split_after_and_this_post'], '</label>
						</li>
						<li>
							<input type="radio" id="selective" name="step2" value="selective" class="input_radio" /> <label for="selective">', $txt['select_split_posts'], '</label>
						</li>
					</ul>
					<br />
					<input type="submit" value="', $txt['split'], '" class="button_submit" />
				</div>
				<span class="botslice"><span></span></span>
			</div>
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
		</form>
	</div>';
}

function template_main()
{
	global $context, $settings, $options, $txt, $scripturl;

	echo '
	<div id="split_topic">
		<h3 class="catbg">
			<span class="left"></span>
			', $txt['split'], '
		</h3>
		<div class="windowbg">
			<span class="topslice"><span></span></span>
			<div class="content">
				<p>', $txt['split_successful'], '</p>
				<ul class="reset">
					<li>
						<a href="', $scripturl, '?board=', $context['current_board'], '.0">', $txt['message_index'], '</a>
					</li>
					<li>
						<a href="', $scripturl, '?topic=', $context['old_topic'], '.0">', $txt['origin_topic'], '</a>
					</li>
					<li>
						<a href="', $scripturl, '?topic=', $context['new_topic'], '.0">', $txt['new_topic'], '</a>
					</li>
				</ul>
			</div>
			<span class="botslice"><span></span></span>
		</div>
	</div>';
}

function template_select()
{
	global $context, $settings, $options, $txt, $scripturl;

	echo '
	<div id="split_topics">
		<form action="', $scripturl, '?action=splittopics;sa=splitSelection;board=', $context['current_board'], '.0" method="post" accept-charset="', $context['character_set'], '"><input type="hidden" name="topic" value="', $context['current_topic'], '" />
			<div id="not_selected" class="align_left">
				<h3 class="catbg">
					<span class="left"></span>
					', $txt['split'], ' - ', $txt['select_split_posts'], '
				</h3>
				<div class="information">
					', $txt['please_select_split'], '
				</div>
				<div class="pagesection">
					<strong>', $txt['pages'], ':</strong> <span id="pageindex_not_selected">', $context['not_selected']['page_index'], '</span>
				</div>
				<table id="table_not_selected" width="100%" class="table_grid bordercolor">';

	foreach ($context['not_selected']['messages'] as $message)
		echo '
					<tr class="windowbg" id="not_selected_', $message['id'], '">
						<td class="smalltext">
							', $message['subject'], ' - ', $message['poster'], '
							<div class="post">', $message['body'], '</div>
						</td>
						<td valign="middle" align="center" width="5%">
							<a href="', $scripturl, '?action=splittopics;sa=selectTopics;subname=', $context['topic']['subject'], ';topic=', $context['topic']['id'], '.', $context['not_selected']['start'], ';start2=', $context['selected']['start'], ';move=down;msg=', $message['id'], '" onclick="return select(\'down\', ', $message['id'], ');"><img src="', $settings['images_url'], '/split_select.gif" alt="-&gt;" /></a>
						</td>
					</tr>';
	echo '
				</table>
			</div>
			<div id="selected" class="align_right">
				<h3 class="catbg">
					<span class="left"></span>
					', $txt['split_selected_posts'], ' (<a href="', $scripturl, '?action=splittopics;sa=selectTopics;subname=', $context['topic']['subject'], ';topic=', $context['topic']['id'], '.', $context['not_selected']['start'], ';start2=', $context['selected']['start'], ';move=reset;msg=0" onclick="return select(\'reset\', 0);">', $txt['split_reset_selection'], '</a>)
				</h3>
				<div class="information">
					', $txt['split_selected_posts_desc'], '
				</div>
				<div class="pagesection">
					<strong>', $txt['pages'], ':</strong> <span id="pageindex_selected">', $context['selected']['page_index'], '</span>
				</div>
				<table id="table_selected" width="100%" class="table_grid bordercolor">';

	if (!empty($context['selected']['messages']))
		foreach ($context['selected']['messages'] as $message)
			echo '
					<tr class="windowbg" id="selected_', $message['id'], '">
						<td width="5%" valign="middle" align="center">
							<a href="', $scripturl, '?action=splittopics;sa=selectTopics;subname=', $context['topic']['subject'], ';topic=', $context['topic']['id'], '.', $context['not_selected']['start'], ';start2=', $context['selected']['start'], ';move=up;msg=', $message['id'], '" onclick="return select(\'up\', ', $message['id'], ');"><img src="', $settings['images_url'], '/split_deselect.gif" alt="&lt;-" /></a>
						</td>
						<td class="smalltext">
							', $message['subject'], ' - ', $message['poster'], '
							<div class="post">', $message['body'], '</div>
						</td>
					</tr>';
	echo '
				</table>
			</div>
			<br class="clear" />
			<p>
				<input type="hidden" name="subname" value="', $context['new_subject'], '" />
				<input type="submit" value="', $txt['split'], '" class="button_submit" />
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
			</p>
		</form>
	</div>
	<br class="clear" />
		<script type="text/javascript"><!-- // --><![CDATA[
			var start = new Array();
			start[0] = ', $context['not_selected']['start'], ';
			start[1] = ', $context['selected']['start'], ';

			function select(direction, msg_id)
			{
				if (window.XMLHttpRequest)
				{
					getXMLDocument(smf_prepareScriptUrl(smf_scripturl) + "action=splittopics;sa=selectTopics;subname=', $context['topic']['subject'], ';topic=', $context['topic']['id'], '." + start[0] + ";start2=" + start[1] + ";move=" + direction + ";msg=" + msg_id + ";xml", onDocReceived);
					return false;
				}
				else
					return true;
			}
			function onDocReceived(XMLDoc)
			{
				var i, j, pageIndex;
				for (i = 0; i < 2; i++)
				{
					pageIndex = XMLDoc.getElementsByTagName("pageIndex")[i];
					setInnerHTML(document.getElementById("pageindex_" + pageIndex.getAttribute("section")), pageIndex.firstChild.nodeValue);
					start[i] = pageIndex.getAttribute("startFrom");
				}
				var numChanges = XMLDoc.getElementsByTagName("change").length, curChange;
				var curSection, curAction, curId, curTable, curRow, curRowIndex, buttonCell, textCell, curData, numRows;
				for (i = 0; i < numChanges; i++)
				{
					curChange = XMLDoc.getElementsByTagName("change")[i];
					curSection = curChange.getAttribute("section");
					curAction = curChange.getAttribute("curAction");
					curId = curChange.getAttribute("id");
					curTable = document.getElementById("table_" + curSection);
					numRows = curTable.rows.length;
					if (curAction == "remove")
						curTable.deleteRow(document.getElementById(curSection + "_" + curId).rowIndex);
					// Insert a message.
					else
					{
						// By default insert the row at the end of the table.
						curRowIndex = -1;
						for (j = curSection == "selected" ? 2 : 3; j < numRows; j++)
						{
							if (parseInt(curTable.rows[j].id.substr(curSection.length + 1)) < curId)
							{
								// This would be a nice place to insert the row.
								curRowIndex = j;
								// We\'re done for now. Escape the loop.
								j = numRows + 1;
							}
						}
						curRow = curTable.insertRow(curRowIndex);
						curRow.className = "windowbg";
						curRow.id = curSection + "_" + curId;
						if (curSection == "selected")
						{
							buttonCell = curRow.insertCell(-1);
							textCell = curRow.insertCell(-1);
						}
						else
						{
							textCell = curRow.insertCell(-1);
							buttonCell = curRow.insertCell(-1);
						}
						setInnerHTML(buttonCell, "<a href=\\"" + smf_prepareScriptUrl(smf_scripturl) + "action=splittopics;sa=selectTopics;subname=', $context['topic']['subject'], ';topic=', $context['topic']['id'], '.', $context['not_selected']['start'], ';start2=', $context['selected']['start'], ';move=" + (curSection == "selected" ? "up" : "down") + ";msg=" + curId + "\\" onclick=\\"return select(\'" + (curSection == "selected" ? "up" : "down") + "\', " + curId + ");\\"><img src=\\"', $settings['images_url'], '/split_" + (curSection == "selected" ? "de" : "") + "select.gif\\" alt=\\"" + (curSection == "selected" ? "&lt;-" : "-&gt;") + "\\" border=\\"0\\" /></a>");
						buttonCell.width = "5%";
						buttonCell.vAlign = "middle";
						buttonCell.align = "center";
						setInnerHTML(textCell, curChange.getElementsByTagName("subject")[0].firstChild.nodeValue + " - " + curChange.getElementsByTagName("poster")[0].firstChild.nodeValue + "<div class=\\"post\\">" + curChange.getElementsByTagName("body")[0].firstChild.nodeValue + "</div>");
						textCell.className = "smalltext";
						// !!! Should something be here?
						//textCell.alt
					}
				}
			}
		// ]]></script>';
}

function template_merge_done()
{
	global $context, $settings, $options, $txt, $scripturl;

	echo '
	<div id="split_topics">
		<h3 class="catbg">
			<span class="left"></span>
			', $txt['merge'], '
		</h3>
		<div class="windowbg">
			<span class="topslice"><span></span></span>
			<div class="content">
				<p>' . $txt['merge_successful'] . '</p>
				<br />
				<ul class="reset">
					<li>
						<a href="' . $scripturl . '?board=' . $context['target_board'] . '.0">' . $txt['message_index'] . '</a>
					</li>
					<li>
						<a href="' . $scripturl . '?topic=' . $context['target_topic'] . '.0">' . $txt['new_merged_topic'] . '</a>
					</li>
				</ul>
			</div>
			<span class="botslice"><span></span></span>
		</div>
	</div>
	<br class="clear" />';
}

function template_merge()
{
	global $context, $settings, $options, $txt, $scripturl;

	echo '
	<div id="merge_topics">
		<h3 class="catbg">
			<span class="left"></span>
			', $txt['merge'], '
		</h3>
		<div class="information">
			', $txt['merge_desc'],  '
		</div>
		<div class="windowbg">
			<span class="topslice"><span></span></span>
			<div class="content">
				<dl class="settings merge_topic">
					<dt>
						<strong>', $txt['topic_to_merge'], ':</strong>
					</dt>
					<dd>
						', $context['origin_subject'], '
					</dd>
					<dt>
						<strong>', $txt['merge_to_topic_id'], ': </strong>
					</dt>
					<dd>
						<form action="', $scripturl , '?action=mergetopics;sa=options" method="post" accept-charset="', $context['character_set'], '" style="display: inline;">
							<input type="hidden" name="topics[]" value="', $context['origin_topic'], '" />
							<input type="text" name="topics[]" class="input_text" />
							<input type="submit" value="', $txt['merge'], '" class="button_submit" />
						</form>
					</dd>';

	if (!empty($context['boards']) && count($context['boards']) > 1)
	{
		echo '
					<dt>
						<strong>' . $txt['target_board'] . ':</strong>
					</dt>
					<dd>
						<form action="' . $scripturl . '?action=mergetopics;from=' . $context['origin_topic'] . ';targetboard=' . $context['target_board'] . ';board=' . $context['current_board'] . '.0" method="post" accept-charset="', $context['character_set'], '">
							<input type="hidden" name="from" value="' . $context['origin_topic'] . '" />
							<select name="targetboard" onchange="this.form.submit();">';
		foreach ($context['boards'] as $board)
			echo '
								<option value="', $board['id'], '"', $board['id'] == $context['target_board'] ? ' selected="selected"' : '', '>', $board['category'], ' - ', $board['name'], '</option>';
		echo '
							</select> <noscript><input type="submit" value="', $txt['go_caps'], '" class="button_submit" /></noscript>
						</form>
					</dd>';
	}

	echo '
				</dl>
			</div>
			<span class="botslice"><span></span></span>
		</div>
		<h3 class="catbg">
			<span class="left"></span>
			', $txt['target_topic'], '
		</h3>
		<div class="pagesection">
			<strong>' . $txt['pages'] . ':</strong> ' . $context['page_index'] . '
		</div>
		<div class="windowbg2">
			<span class="topslice"><span></span></span>
			<div class="content">
				<ul class="reset merge_topics">';

	$merge_button = create_button('merge.gif', 'merge', '');

	foreach ($context['topics'] as $topic)
		echo '

					<li>
						<a href="' . $scripturl . '?action=mergetopics;sa=options;board=' . $context['current_board'] . '.0;from=' . $context['origin_topic'] . ';to=' . $topic['id'] . ';' . $context['session_var'] . '=' . $context['session_id'] . '">' . $merge_button . '</a>&nbsp;
						<a href="' . $scripturl . '?topic=' . $topic['id'] . '.0" target="_blank" class="new_win">' . $topic['subject'] . '</a> ' . $txt['started_by'] . ' ' . $topic['poster']['link'] . '
					</li>';

	echo '
					</ul>
				</div>
			<span class="botslice"><span></span></span>
		</div>
		<div class="pagesection">
			<strong>' . $txt['pages'] . ':</strong> ' . $context['page_index'] . '
		</div>
	</div>
	<br class="clear" />';
}

function template_merge_extra_options()
{
	global $context, $settings, $options, $txt, $scripturl;

	echo '
	<div id="merge_topics">
		<form action="', $scripturl, '?action=mergetopics;sa=execute;" method="post" accept-charset="', $context['character_set'], '">
			<h3 class="titlebg">
				<span class="left"></span>
				', $txt['merge_topic_list'], '
			</h3>
			<table width="100%" class="bordercolor table_grid">
			<thead>
				<tr class="catbg">
					<th scope="col" class="smalltext" width="10px">', $txt['merge_check'], '</th>
					<th scope="col" class="smalltext">', $txt['subject'], '</th>
					<th scope="col" class="smalltext">', $txt['started_by'], '</th>
					<th scope="col" class="smalltext">', $txt['last_post'], '</th>
					<th scope="col" class="smalltext" width="20px">' . $txt['merge_include_notifications'] . '</th>
				</tr>
			</thead>
			<tbody>';
	foreach ($context['topics'] as $topic)
		echo '
				<tr>
					<td class="windowbg2" align="center">
						<input type="checkbox" class="input_check" name="topics[]" value="' . $topic['id'] . '" checked="checked" />
					</td>
					<td class="windowbg2" align="center">
						<a href="' . $scripturl . '?topic=' . $topic['id'] . '.0" target="_blank" class="new_win">' . $topic['subject'] . '</a>
					</td>
					<td class="windowbg2" align="center">
						', $topic['started']['link'], '<br />
						<span class="smalltext">', $topic['started']['time'], '</span>
					</td>
					<td class="windowbg2" align="center">
						' . $topic['updated']['link'] . '<br />
						<span class="smalltext">', $topic['updated']['time'], '</span>
					</td>
					<td class="windowbg2" align="center">
						<input type="checkbox" class="input_check" name="notifications[]" value="' . $topic['id'] . '" checked="checked" />
					</td>
				</tr>';
	echo '
			</tbody>
			</table>
			<br />
			<div class="windowbg">
				<span class="topslice"><span></span></span>
				<div class="content">';

	echo '
					<fieldset id="merge_subject" class="merge_options">
						<legend>', $txt['merge_select_subject'], '</legend>
						<select name="subject" onchange="this.form.custom_subject.style.display = (this.options[this.selectedIndex].value != 0) ? \'none\': \'\' ;">';
	foreach ($context['topics'] as $topic)
		echo '
							<option value="', $topic['id'], '"' . ($topic['selected'] ? ' selected="selected"' : '') . '>', $topic['subject'], '</option>';
	echo '
							<option value="0">', $txt['merge_custom_subject'], ':</option>
						</select>
						<br /><input type="text" name="custom_subject" size="60" id="custom_subject" class="input_text custom_subject" style="display: none;" />
						<br />
						<label for="enforce_subject"><input type="checkbox" class="input_check" name="enforce_subject" id="enforce_subject" value="1" /> ', $txt['merge_enforce_subject'], '</label>
					</fieldset>';

	if (!empty($context['boards']) && count($context['boards']) > 1)
	{
		echo '
					<fieldset id="merge_board" class="merge_options">
						<legend>', $txt['merge_select_target_board'], '</legend>
						<ul class="reset">';
		foreach ($context['boards'] as $board)
			echo '
							<li>
								<input type="radio" name="board" value="' . $board['id'] . '"' . ($board['selected'] ? ' checked="checked"' : '') . ' class="input_radio" /> ' . $board['name'] . '
							</li>';
		echo '
						</ul>
					</fieldset>';
	}
	if (!empty($context['polls']))
	{
		echo '
					<fieldset id="merge_poll" class="merge_options">
						<legend>' . $txt['merge_select_poll'] . '</legend>
						<ul class="reset">';
		foreach ($context['polls'] as $poll)
			echo '
							<li>
								<input type="radio" name="poll" value="' . $poll['id'] . '"' . ($poll['selected'] ? ' checked="checked"' : '') . ' class="input_radio" /> ' . $poll['question'] . ' (' . $txt['topic'] . ': <a href="' . $scripturl . '?topic=' . $poll['topic']['id'] . '.0" target="_blank" class="new_win">' . $poll['topic']['subject'] . '</a>)
							</li>';
		echo '
							<li>
								<input type="radio" name="poll" value="-1" class="input_radio" /> (' . $txt['merge_no_poll'] . ')
							</li>
						</ul>
					</fieldset>';
	}
	echo '
					<input type="submit" value="' . $txt['merge'] . '" class="button_submit" />
					<input type="hidden" name="sa" value="execute" />
				</div>
				<span class="botslice"><span></span></span>
			</div>
		</form>
	</div>
	<br class="clear" />';
}

?>