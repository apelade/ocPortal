<tr>
	<th colspan="2">
		<h2 class="ocf_post_history_header">{!ACTION}: {ACTION}</h2>
	</th>
</tr>
<tr>
	<th{+START,IF,{$NOT,{$VALUE_OPTION,html5}}} abbr="{!DETAILS}"{+END} class="de_th ocf_history_post_meta">
		{+START,IF_NON_EMPTY,{ACTION_DATE_AND_TIME}}
			<p><span class="ocf_history_post_meta_head">{!ACTION_DATE_TIME}</span>:<br />{ACTION_DATE_AND_TIME*}</p>
		{+END}
		<p><span class="ocf_history_post_meta_head">{!POST_DATE_TIME}</span>:<br />{CREATE_DATE_AND_TIME*}</p>
		<p><span class="ocf_history_post_meta_head">{!OWNER}</span>: {OWNER_MEMBER}</p>
		{+START,IF_NON_EMPTY,{ALTERER_MEMBER}}
			<p><span class="ocf_history_post_meta_head">{!MODERATOR}</span>: {ALTERER_MEMBER}</p>
		{+END}
	</th>
	<td class="ocf_history_post">
		<form title="{LABEL*}" action="index.php" method="post">
			<p class="ocf_history_post_head"><label for="history_post_{RAND*}">{LABEL*}</label>:</p>
			<div class="constrain_field">
				<textarea class="wide_field" id="history_post_{RAND*}" name="history_post" cols="30" rows="10" readonly="readonly">{BEFORE*}</textarea>
			</div>
		</form>
	</td>
</tr>
<tr>
	<td class="ocf_history_post_links">
		{LINK}
	</td>
	<td class="ocf_history_post_buttons">
		{BUTTONS}
	</td>
</tr>
