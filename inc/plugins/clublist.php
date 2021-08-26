<?php

// Direktzugriff auf die Datei aus Sicherheitsgründen sperren
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// HOOKS
// Zusatz-Funktion der Einstellungen
$plugins->add_hook('admin_config_settings_change', 'clublist_settings_change');
$plugins->add_hook('admin_settings_print_peekers', 'clublist_settings_peek');
// Online Anzeige
$plugins->add_hook("fetch_wol_activity_end", "clublist_online_activity");
$plugins->add_hook("build_friendly_wol_location_end", "clublist_online_location");
// Clublisteseite
$plugins->add_hook("misc_start", "clublist_misc");
// Teambenachrichtigung auf dem Index
$plugins->add_hook('global_start', 'clublist_global');
// Mod-CP
$plugins->add_hook('modcp_nav', 'clublist_modcp_nav');
$plugins->add_hook("modcp_start", "clublist_modcp");
// MyAlerts
if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
	$plugins->add_hook("global_start", "clublist_myalert_alerts");
}
// Profil
$plugins->add_hook("member_profile_end", "clublist_memberprofile");

// Die Informationen, die im Pluginmanager angezeigt werden
function clublist_info()
{
	return array(
		"name"		=> "Interaktive Clubliste",
		"description"	=> "Dieses Plugin erweitert das Board um eine interaktive Clubliste. Ausgewählte Usergruppen können neue Clubs hinzufügen und Clubs beitreten. Clubs müssen vom Team erst freigeschaltet werden im Mod-CP. Nach der Freischaltung können User sich mit ihren Accounts als Mitglieder der Clubs eintragen. Wenn nicht anders eingestellt, so oft wie die User möchten. Mitgliedschaften werden im Profil angezeigt.",
		"website"	=> "https://github.com/little-evil-genius/Interaktive-Clubliste",
		"author"	=> "little.evil.genius",
		"authorsite"	=> "https://storming-gates.de/member.php?action=profile&uid=1712",
		"version"	=> "1.0",
		"compatibility" => "18*"
	);
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin installiert wird (optional).
function clublist_install()
{
	global $db, $cache, $mybb;

    // Datenbank-Tabelle erstellen

	// CLUBS - HIER WERDEN DIE INFOS ZU DEN CLUBS GESPEICHERT
	$db->query("CREATE TABLE ".TABLE_PREFIX."clubs(
        `cid` int(10) NOT NULL AUTO_INCREMENT,
		`type` VARCHAR(500) NOT NULL,
		`clubname` VARCHAR(1000) COLLATE utf8_general_ci NOT NULL,
		`clubtime` VARCHAR(1000) COLLATE utf8_general_ci NOT NULL,
        `clubtext` VARCHAR(5000) COLLATE utf8_general_ci NOT NULL,
        `conductor` int(1) NOT NULL,
        `position` int(1) NOT NULL,
        `accepted` int(1) NOT NULL,
        `createdby` int(11) NOT NULL,
        PRIMARY KEY(`cid`),
        KEY `cid` (`cid`)
        )
        ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1
        ");
        
	// MITGLIEDER DER CLUBS - HIER WERDEN DIE USER DER CLUBS GESPEICHERT
	$db->query("CREATE TABLE ".TABLE_PREFIX."clubs_user(
		`ucid` int(10) NOT NULL AUTO_INCREMENT,
		`cid` int(10) NOT NULL,
		`uid` int(10) NOT NULL,
		`position` VARCHAR(1000) COLLATE utf8_general_ci NOT NULL,
		PRIMARY KEY(`ucid`),
		KEY `ucid` (`ucid`)
		)
		ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1
		");

	// EINSTELLUNGEN HINZUFÜGEN
    $setting_group = array(
        'name'          => 'clublist',
        'title'         => 'Clubliste',
        'description'   => 'Einstellungen für die Clubliste',
        'disporder'     => 1,
        'isdefault'     => 0
    );
        
        $gid = $db->insert_query("settinggroups", $setting_group); 
        
    $setting_array = array(
        'clublist_add_allow_groups' => array(
            'title' => 'Erlaubte Gruppen',
            'description' => 'Welche Gruppen dürfen neue Clubs erstellen?',
            'optionscode' => 'groupselect',
            'value' => '4', // Default
            'disporder' => 1
        ),

        'clublist_member_allow_groups' => array(
            'title' => 'Erlaubte Gruppen',
            'description' => 'Welche Gruppen dürfen sich als Mitglied eintragen?',
            'optionscode' => 'groupselect',
            'value' => '4', // Default
            'disporder' => 2
        ),

        'clublist_type' => array(
            'title' => 'Bereiche',
            'description' => 'In welche Bereiche können die Clubs eingeordnet werden?',
            'optionscode' => 'text',
            'value' => 'Schulclubs, Clubs, Vereine, Ehrenämter', // Default
            'disporder' => 3
        ),

        'clublist_delete' => array(
            'title' => 'Löschfunktion',
            'description' => 'Dürfen User ihre selbsterstellen Clubs löschen?',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 4
        ),

        'clublist_edit' => array(
            'title' => 'Bearbeitungsfunktion',
            'description' => 'Dürfen User ihre selbsterstellen Clubs bearbeiten? Das Team muss bearbeitungen nicht erneut überprüfen.',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 5
        ),

        'clublist_limit' => array(
            'title' => 'Begrenzte Mitgliedschaft',
            'description' => 'Dürfen User nur eine bestimmte Anzahl von Clubs beitreten?',
            'optionscode' => 'yesno',
            'value' => '0', // Default
            'disporder' => 7
        ),

        'clublist_limit_number' => array(
            'title' => 'Anzahl der Mitgliedschaften',
            'description' => 'In wie viele Clubs dürfen die User eintreten?',
            'optionscode' => 'text',
            'value' => '3', // Default
            'disporder' => 8
        ),

        'clublist_filter' => array(
            'title' => 'Filterfunktion',
            'description' => 'Soll es auf der Clublisten-Seite eine Filterfunktion geben?',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 9
        ),

        'clublist_multipage' => array(
            'title' => 'Multipage-Navigation',
            'description' => 'Sollen die Clubs ab einer bestimmten Anzahl auf der Clublisten-Seite auf mehrere Seiten aufgeteilt werden?',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 10
        ),

        'clublist_multipage_show' => array(
            'title' => 'Anzahl der Clubs (Multipage-Navigation)',
            'description' => 'Wie viele Clubs sollen auf einer Seite angezeigt werden?',
            'optionscode' => 'text',
            'value' => '10', // Default
            'disporder' => 11
        ),

        'clublist_lists' => array(
            'title' => 'Listen PHP (Navigation Ergänzung)',
            'description' => 'Wie heißt die Hauptseite eurer Listen-Seite? Dies dient zur Ergänzung der Navigation. Falls nicht gewünscht einfach leer lassen.',
            'optionscode' => 'text',
            'value' => 'listen.php', // Default
            'disporder' => 12
        ),
    );
        
        foreach($setting_array as $name => $setting)
        {
            $setting['name'] = $name;
            $setting['gid']  = $gid;
            $db->insert_query('settings', $setting);
        }
    
        rebuild_settings();
	
        
    // TEMPLATES ERSTELLEN

	// Clubliste - Hauptseite
	$insert_array = array(
		'title'        => 'clublist',
		'template'    => $db->escape_string('<html>
		<head>
			<title>{$mybb->settings[\'bbname\']} - {$lang->clublist}</title>
			{$headerinclude}
		</head>
		<body>
			{$header}
			<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
				<tr>
					<td class="thead"><span class="smalltext"><strong>{$lang->clublist}</strong></span></td>
				</tr>
				{$club_filter}
				{$club_add}
				<tr>
					<td class="trow2" width="100%">
						{$multipage}<br />
						{$club_bit}
						{$multipage}<br />
					</td>
				</tr>
			</table>
			{$footer}
		</body>
	</html>'),
		'sid'        => '-1',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);


	// Club hinzufügen
	$insert_array = array(
		'title'        => 'clublist_add',
		'template'    => $db->escape_string('<tr>
		<td class="trow2" align="center">
			<form id="add_clubs" method="post" action="misc.php?action=add_clubs">
				<input type="hidden" name="action" value="add_clubs">					
				<details>
					<summary>{$lang->clublist_add}</summary>
					<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
						<tr>
							<td class="trow2" valign="top" width="50%"> 
								<div class="tcat" style="margin-bottom: 5px;text-align: center;">{$lang->clublist_add_name}</div>
								<input type="text" class="textbox" name="clubname" id="clubname" maxlength="500" placeholder="{$lang->clublist_add_name_desc}" style="margin-bottom: 5px;width: 99%;" />
				
								<div class="tcat" style="margin-bottom: 5px;text-align: center;">{$lang->clublist_add_time}</div>
								<input type="text" class="textbox" name="clubtime" id="clubtime" maxlength="500" placeholder="{$lang->clublist_add_time_desc}" style="margin-bottom: 5px;width: 99%;" />
				
								<div class="tcat" style="margin-bottom: 5px;text-align: center;">{$lang->clublist_add_conductor}</div>
								<input type="text" name="conductor" id="conductor" class="textbox" size="40" maxlength="1155" v style="width: 100%;margin-bottom: 5px;" />
				
								<div class="tcat" style="margin-bottom: 5px;text-align: center;">{$lang->clublist_add_position}</div>
								<select name="position" class="input" style="width: 100%;">
									<option value="">Option wählen</option>
									<option value="1">Position möglich</option>
									<option value="0">Position nicht möglich</option>
								</select>
							</td>
		
							<td class="trow2" valign="top"> 
								<div class="tcat" style="margin-bottom: 5px;text-align: center;">{$lang->clublist_add_type}</div>
								<select name="type" class="input" style="margin-bottom: 5px;width: 100%;">	
									<option value="">Kategorie wählen</option>	
									{$type_select}
								</select>
		
								<div class="tcat" style="margin-bottom: 5px;text-align: center;">{$lang->clublist_add_desc}</div>
								<textarea class="textbox" name="clubtext" id="clubtext" style="width: 99%;height: 151px;" maxlength="5000" placeholder="{$lang->clublist_add_desc_desc}" ></textarea>
							</td>
						</tr>
						<tr>	
							<td class="trow2" colspan=2>
								<center>  
									<input type="submit" value="{$lang->clublist_add_send}" name="add_clubs" class="button">  
								</center>
							</td>
						</tr>
					</table>
				</details>
			</form>
		</td>
	</tr>
	
	<link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css?ver=1807">
	<script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js?ver=1806"></script>
	<script type="text/javascript">
	<!--
	if(use_xmlhttprequest == "1")
	{
		MyBB.select2();
		$("#conductor").select2({
			placeholder: "{$lang->search_user}",
			minimumInputLength: 2,
			maximumSelectionSize: \'\',
			multiple: true,
			ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
				url: "xmlhttp.php?action=get_users",
				dataType: \'json\',
				data: function (term, page) {
					return {
						query: term, // search term
					};
				},
				results: function (data, page) { // parse the results into the format expected by Select2.
					// since we are using custom formatting functions we do not need to alter remote JSON data
					return {results: data};
				}
			},
			initSelection: function(element, callback) {
				var query = $(element).val();
				if (query !== "") {
					var newqueries = [];
					exp_queries = query.split(",");
					$.each(exp_queries, function(index, value ){
						if(value.replace(/\s/g, \'\') != "")
						{
							var newquery = {
								id: value.replace(/,\s?/g, ","),
								text: value.replace(/,\s?/g, ",")
							};
							newqueries.push(newquery);
						}
					});
					callback(newqueries);
				}
			}
		});
	}
	// -->
	</script>'),
		'sid'        => '-1',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);


	// Einzelner Club
	$insert_array = array(
		'title'        => 'clublist_bit',
		'template'    => $db->escape_string('<table cellspacing="0" cellpadding="5">
		<tbody>
			<tr>
				<td colspan="2" class="thead">
					{$clubname} » {$type}
				</td>
			</tr>
			<tr>
				<td colspan="2" class="tcat">
					{$lang->clublist_conductor} {$conductor} 
				</td>
			</tr>
			<tr>
				<td colspan="2">
					{$lang->clublist_time} {$clubtime} 
				</td>
			</tr>
			<tr>        
				<td class="tcat" width="55%">
					{$lang->clublist_desc}
				</td>
				<td class="tcat" width="45%">
					{$lang->clublist_member}
				</td>
			</tr>
			<tr>        
				<td class="trow2" align="justify" width="55%" valign="top">
					<div style="max-height: 80px; overflow: auto; padding-right: 10px; scrollbar-width: none;">
						{$clubtext}
					</div>
				</td>
				<td class="trow2" width="45%" valign="top">
					<div style="max-height: 80px; overflow: auto;">
						{$user_bit}
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					{$joinlink} {$edit} {$delete}
				</td>
			</tr>
		</tbody>
	</table>'),
		'sid'        => '-1',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);


	// Mitglieder eines Clubs
	$insert_array = array(
		'title'        => 'clublist_bit_users',
		'template'    => $db->escape_string('» {$user} {$position}<br>'),
		'sid'        => '-1',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);


	// Club bearbeiten
	$insert_array = array(
		'title'        => 'clublist_edit',
		'template'    => $db->escape_string('<html>
		<head>
			<title>{$mybb->settings[\'bbname\']} - {$lang->clublist_edit}</title>
			{$headerinclude}
		</head>
		<body>
			{$header}       
			<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
				<tr>
					<td colspan="2"><div class="tcat">{$lang->clublist_edit}</div></td>
				</tr>
				<tr>
					<td>
						<form id="edit_clubs" method="post" action="misc.php?action=clublist_edit&cid={$cid}">
							<input type="hidden" name="cid" id="cid" value="{$cid}" class="textbox" />
							<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
								<tr>
									<td class="trow2" valign="top" width="50%"> 
										<div class="tcat" style="margin-bottom: 5px;text-align: center;">{$lang->clublist_add_name}</div>
										<input type="text" class="textbox" name="clubname" id="clubname" maxlength="500" value="{$clubname}" style="margin-bottom: 5px;width: 99%;" />
				
										<div class="tcat" style="margin-bottom: 5px;text-align: center;">{$lang->clublist_add_time}</div>
										<input type="text" class="textbox" name="clubtime" id="clubtime" maxlength="500" value="{$clubtime}" style="margin-bottom: 5px;width: 99%;" />
				
										<div class="tcat" style="margin-bottom: 5px;text-align: center;">{$lang->clublist_add_conductor}</div>
										<input type="text" name="conductor" id="conductor" class="textbox" value="{$edit[\'conductor\']}" size="40" maxlength="1155" v style="width: 100%;margin-bottom: 5px;" />
				
										<div class="tcat" style="margin-bottom: 5px;text-align: center;">{$lang->clublist_add_position}</div>
										<select name="position" class="input" style="width: 100%;">
											<option value="{$position}">{$position_word}</option>
											<option value="1">Position möglich</option>
											<option value="0">Position nicht möglich</option>
										</select>
									</td>
		
									<td class="trow2" valign="top"> 
										<div class="tcat" style="margin-bottom: 5px;text-align: center;">{$lang->clublist_add_type}</div>
										<select name="type" class="input" style="margin-bottom: 5px;width: 100%;">	
											<option value="{$type}">{$type}</option>	
											{$type_select}
										</select>
		
										<div class="tcat" style="margin-bottom: 5px;text-align: center;">{$lang->clublist_add_desc}</div>
										<textarea class="textbox" name="clubtext" id="clubtext" style="width: 99%;height: 151px;" maxlength="5000">{$clubtext}</textarea>
									</td>
								</tr>
								<tr>	
									<td class="trow2" colspan=2>
										<center>  
											<input type="submit" value="{$lang->clublist_edit_send}" name="edit_clubs"  id="submit" class="button"> 
										</center>
									</td>
								</tr>
							</table>
						</form>
					</td>
				</tr>
			</table>
			{$footer}
		</body>
	</html>
	
	<link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css?ver=1807">
	<script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js?ver=1806"></script>
	<script type="text/javascript">
	<!--
	if(use_xmlhttprequest == "1")
	{
		MyBB.select2();
		$("#conductor").select2({
			placeholder: "{$lang->search_user}",
			minimumInputLength: 2,
			maximumSelectionSize: \'\',
			multiple: true,
			ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
				url: "xmlhttp.php?action=get_users",
				dataType: \'json\',
				data: function (term, page) {
					return {
						query: term, // search term
					};
				},
				results: function (data, page) { // parse the results into the format expected by Select2.
					// since we are using custom formatting functions we do not need to alter remote JSON data
					return {results: data};
				}
			},
			initSelection: function(element, callback) {
				var query = $(element).val();
				if (query !== "") {
					var newqueries = [];
					exp_queries = query.split(",");
					$.each(exp_queries, function(index, value ){
						if(value.replace(/\s/g, \'\') != "")
						{
							var newquery = {
								id: value.replace(/,\s?/g, ","),
								text: value.replace(/,\s?/g, ",")
							};
							newqueries.push(newquery);
						}
					});
					callback(newqueries);
				}
			}
		});
	}
	// -->
	</script>'),
		'sid'        => '-1',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);


	// Filter-Funktion
	$insert_array = array(
		'title'        => 'clublist_filter',
		'template'    => $db->escape_string('<tr>
		<td class="trow2" align="center">
			<div class="float_left_" style="margin:auto" valign="middle">
				<form method="get" id="search_clubs" action="misc.php?action=clublist">
					<input type="hidden" name="action" value="clublist" />
					{$lang->clublist_filter}
					<select name="type" id="type">
						<option value="">{$lang->clublist_filter_option}</option>
						{$filter_type}
					</select>
					<input type="submit" value="{$lang->clublist_filter_button}" class="button" />
				</form>
			</div>
		</td>
	</tr>'),
		'sid'        => '-1',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);


	// Beitreten mit Position
	$insert_array = array(
		'title'        => 'clublist_join_position',
		'template'    => $db->escape_string('<form action="misc.php?action=clublist&join_position={$cid}" method="post">   
		<input type="hidden" name="cid" id="cid" value="{$cid}"/>
		<table style="margin: 10px;width: 350px;">                         	
			<tbody>
				<tr>
					<td class="tcat">
						{$lang->clublist_join_position}
					</td>                                                     
				</tr>
				<tr>
					<td>
						<input type="text" class="textbox" name="position" id="position" style="width: 98%;" placeholder="{$lang->clublist_join_position_desc}" required>
					</td>
				</tr>
				<tr>
					<td colspan="2" align="center">
						<input type="submit" name="join_position" id="submit" class="button" value="{$lang->clublist_join_position_send}">
					</td>
				</tr>
			</tbody>
		</table>
	</form>'),
		'sid'        => '-1',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);


	// Profil
	$insert_array = array(
		'title'        => 'clublist_memberprofile',
		'template'    => $db->escape_string('<table border="0" cellspacing="0" cellpadding="5" class="tborder">	
		<tbody>
			<tr>
				<td class="thead" colspan=2>
					{$lang->clublist_memberprofile}
				</td>
			</tr>
			<tr>
				<td class="tcat" width="50%">
					{$lang->clublist_memberprofile_member}
				</td>
				<td class="tcat" width="50%">
					{$lang->clublist_memberprofile_conductor}
				</td>
			</tr>	
			<tr>
				<td valign="top" class="trow1" width="50%">
					{$member_clubs_bit}
				</td>
				<td valign="top" class="trow1" width="50%">
					{$conductor_clubs_bit}
				</td>
			</tr>		
		</tbody>
	</table>
	<br>'),
		'sid'        => '-1',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);


	// Profil - Einzelausgabe - Mitgliedschaften
	$insert_array = array(
		'title'        => 'clublist_memberprofile_bit',
		'template'    => $db->escape_string('<b>{$clubname} ({$type}):</b> {$clubtime} {$position}<br>'),
		'sid'        => '-1',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);


	// Profil - Einzelausgabe - Keine Mitgliedschaften
	$insert_array = array(
		'title'        => 'clublist_memberprofile_bit_none',
		'template'    => $db->escape_string('<div style="text-align:center;margin:10px auto;">{$lang->clublist_memberprofile_member_none}</div>'),
		'sid'        => '-1',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);


	// Profil - Einzelausgabe - Leiter
	$insert_array = array(
		'title'        => 'clublist_memberprofile_conductor_bit',
		'template'    => $db->escape_string('<b>{$clubname} ({$type}):</b> {$clubtime}<br>'),
		'sid'        => '-1',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);


	// Profil - Einzelausgabe - Kein Leiter
	$insert_array = array(
		'title'        => 'clublist_memberprofile_conductor_bit_none',
		'template'    => $db->escape_string('<div style="text-align:center;margin:10px auto;">{$lang->clublist_memberprofile_conductor_none}</div>'),
		'sid'        => '-1',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);


	// Mod-CP Seite
	$insert_array = array(
		'title'        => 'clublist_modcp',
		'template'    => $db->escape_string('<html>
		<head>
			<title>{$mybb->settings[\'bbname\']} -  {$lang->clublist_modcp}</title>
			{$headerinclude}
		</head>
		<body>
			{$header}
			<table width="100%" border="0" align="center">
				<tr>
					{$modcp_nav}
					<td valign="top">
						<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
							<tr>
								<td class="thead">
									<strong>{$lang->clublist_modcp}</strong>
								</td>
							</tr>
							<tr>
								<td class="trow2">
									{$clublist_modcp_bit}
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			{$footer}
		</body>
	</html>'),
		'sid'        => '-1',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);


	// Mod-CP Einzelner Club
	$insert_array = array(
		'title'        => 'clublist_modcp_bit',
		'template'    => $db->escape_string('<table width="100%" border="0">
        <tbody>
            <tr>
                <td class="thead" colspan="2">{$clubname} » {$type}</td>
            </tr>
            <tr>
                <td align="center" colspan="2">{$lang->clublist_modcp_time} <b>{$clubtime}</b></td>
            </tr>
			<tr>
                <td align="center" colspan="2"><b>{$position}</b> » {$lang->clublist_modcp_conductor} <b>{$conductor}</b> » {$lang->clublist_modcp_createdby} <b>{$createdby}</b></td>
            </tr>
            <tr>
                <td class="trow2" colspan="2" align="justify">
                    {$clubtext}
                </td> 
            </tr>
            <tr>
                <td class="trow2" align="center" width="50%">
                    <a href="modcp.php?action=clublist&accepted={$cid}" class="button">{$lang->clublist_modcp_accepted}</a>
                </td>
                
                <td class="trow2" align="center" width="50%">
                    <a href="modcp.php?action=clublist&declined={$cid}" class="button">{$lang->clublist_modcp_declined}</a> 
                </td>
            </tr>
        </tbody>
    </table>'),
		'sid'        => '-1',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);


	// Mod-CP Navi
	$insert_array = array(
		'title'        => 'clublist_modcp_nav',
		'template'    => $db->escape_string('<tr>
		<td class="trow1 smalltext">
			<a href="modcp.php?action=clublist" class="modcp_nav_item modcp_nav_reports">{$lang->clublist_modcp}</a>			
		</td>
	</tr>'),
		'sid'        => '-1',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);


}
 
// Funktion zur Überprüfung des Installationsstatus; liefert true zurürck, wenn Plugin installiert, sonst false (optional).
function clublist_is_installed()
{
	global $db, $mybb;

    if ($db->table_exists("clubs")) {
        return true;
    }
    return false;

} 
 
// Diese Funktion wird aufgerufen, wenn das Plugin deinstalliert wird (optional).
function clublist_uninstall()
{
	global $db;

    //DATENBANK LÖSCHEN
    if($db->table_exists("clubs"))
    {
        $db->drop_table("clubs");
    }

	if($db->table_exists("clubs_user"))
    {
        $db->drop_table("clubs_user");
    }
    
    // EINSTELLUNGEN LÖSCHEN
    $db->delete_query('settings', "name LIKE 'clublist%'");
    $db->delete_query('settinggroups', "name = 'clublist'");

    rebuild_settings();

    // TEMPLATES LÖSCHEN
    $db->delete_query("templates", "title LIKE 'clublist%'");
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin aktiviert wird.
function clublist_activate()
{
	global $db, $cache;

    // MyALERTS STUFF
    if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager) {
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

        // Alert beim annehmen
		$alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode('clublist_accepted'); // The codename for your alert type. Can be any unique string.
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);

		$alertTypeManager->add($alertType);

        // Alert beim ablehnen
        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode('clublist_declined'); // The codename for your alert type. Can be any unique string.
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);

		$alertTypeManager->add($alertType);
    }
    
    // VARIABLEN EINFÜGEN
    require MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets('member_profile', '#'.preg_quote('{$awaybit}').'#', '{$awaybit} {$member_profile_clubs}');
	find_replace_templatesets('header', '#'.preg_quote('{$bbclosedwarning}').'#', '{$new_club_alert} {$bbclosedwarning}');
	find_replace_templatesets('modcp_nav_users', '#'.preg_quote('{$nav_ipsearch}').'#', '{$nav_ipsearch} {$nav_clublist}');
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin deaktiviert wird.
function clublist_deactivate()
{
	global $db, $cache;

    // VARIABLEN ENTFERNEN
    require MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets("header", "#".preg_quote('{$new_club_alert}')."#i", '', 0);
    find_replace_templatesets("member_profile", "#".preg_quote('{$member_profile_clubs}')."#i", '', 0);
    find_replace_templatesets("modcp_nav_users", "#".preg_quote('{$nav_clublist}')."#i", '', 0);

    // MyALERT STUFF
    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager) {
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

		$alertTypeManager->deleteByCode('clublist_declined');
        $alertTypeManager->deleteByCode('clublist_accepted');
	}
}

##############################
### FUNKTIONEN - THE MAGIC ###
##############################

// ZUSATZ-FUNKTION EINSTELLUNGEN
function clublist_settings_change()
{
    global $db, $mybb, $clublist_settings_peeker;

    $result = $db->simple_select('settinggroups', 'gid', "name='clublist'", array("limit" => 1));
    $group = $db->fetch_array($result);
    $clublist_settings_peeker = ($mybb->input['gid'] == $group['gid']) && ($mybb->request_method != 'post');
}
function clublist_settings_peek(&$peekers)
{
    global $mybb, $clublist_settings_peeker;

    if ($clublist_settings_peeker) {
       $peekers[] = 'new Peeker($(".setting_clublist_limit"), $("#row_setting_clublist_limit_number"),/1/,true)';
    }
	if ($clublist_settings_peeker) {
		$peekers[] = 'new Peeker($(".setting_clublist_multipage"), $("#row_setting_clublist_multipage_show"),/1/,true)';
	 }
}

// ONLINE ANZEIGE - WER IST WO
function clublist_online_activity($user_activity) {
global $parameters;

    $split_loc = explode(".php", $user_activity['location']);
    if($split_loc[0] == $user['location']) {
        $filename = '';
    } else {
        $filename = my_substr($split_loc[0], -my_strpos(strrev($split_loc[0]), "/"));
    }
    
    switch ($filename) {
        case 'misc':
        if($parameters['action'] == "clublist" && empty($parameters['site'])) {
            $user_activity['activity'] = "clublist";
        }
        if($parameters['action'] == "clublist_edit" && empty($parameters['site'])) {
            $user_activity['activity'] = "clublist_edit";
        }
        break;
    }
      
return $user_activity;
}
function clublist_online_location($plugin_array) {
global $mybb, $theme, $lang;

	if($plugin_array['user_activity']['activity'] == "clublist") {
		$plugin_array['location_name'] = "Sieht sich die <a href=\"misc.php?action=clublist\">Clubliste</a> an.";
	}
    if($plugin_array['user_activity']['activity'] == "clublist_edit") {
		$plugin_array['location_name'] = "Bearbeitet gerade ein Club.";
	}

return $plugin_array;
}

// TEAMHINWEIS ÜBER NEUE CLUBS
function clublist_global(){
    global $db, $cache, $mybb, $templates, $new_club_alert;

     // NEUE CLUBS
     $select_clubs = $db->query("SELECT *
        FROM " . TABLE_PREFIX . "clubs
        where accepted = 0
        ");

    $count_clubs = mysqli_num_rows($select_clubs);
     
     if( $mybb->usergroup['canmodcp'] == "1" && $count_clubs == "1"){
         $new_club_alert = "<div class=\"red_alert\"><a href=\"modcp.php?action=clublist\">{$count_clubs} neuer Club muss freigeschaltet werden</a></div>";
     } elseif ($mybb->usergroup['canmodcp'] == "1" && $count_clubs > "1") {
        $new_club_alert = "<div class=\"red_alert\"><a href=\"modcp.php?action=clublist\">{$count_clubs} neue Clubs müssen freigeschaltet werden</a></div>";
    }

}

// DIE SEITEN
function clublist_misc() {
    global $db, $cache, $mybb, $lang, $templates, $theme, $header, $headerinclude, $footer, $club_add, $club_bit, $joinlink, $position_join, $type_select;

    // SPRACHDATEI LADEN
    $lang->load('clublist');
    
    // USER-ID
    $user_id = $mybb->user['uid'];

    // ACTION-BAUM BAUEN
    $mybb->input['action'] = $mybb->get_input('action');

	// EINSTELLUNGEN HOLEN
    $clublist_add_allow_groups_setting = $mybb->settings['clublist_add_allow_groups'];
    $clublist_member_allow_groups_setting = $mybb->settings['clublist_member_allow_groups'];
    $clublist_type_setting = $mybb->settings['clublist_type'];
    $clublist_delete_setting = $mybb->settings['clublist_delete'];
    $clublist_edit_setting = $mybb->settings['clublist_edit']; 
    $clublist_limit_setting = $mybb->settings['clublist_limit']; 
    $clublist_limit_number_setting = $mybb->settings['clublist_limit_number']; 
    $clublist_filter_setting = $mybb->settings['clublist_filter']; 
    $clublist_multipage_setting = $mybb->settings['clublist_multipage']; 
    $clublist_multipage_show_setting = $mybb->settings['clublist_multipage_show'];
    $clublist_lists_setting = $mybb->settings['clublist_lists']; 

	// AUSWAHLMÖGLICHKEIT DROPBOX GENERIEREN
	// Kategorien
	$clubs_type = explode (", ", $clublist_type_setting);
	foreach ($clubs_type as $type) {
		$type_select .= "<option value='{$type}'>{$type}</option>";
	}

	// CLUBSEITE
    if($mybb->input['action'] == "clublist") {

    // NAVIGATION
	if(!empty($clublist_lists_setting)){
		add_breadcrumb("Listen", "$clublist_lists_setting");
		add_breadcrumb($lang->clublist, "misc.php?action=clublist");
    } else{
		add_breadcrumb($lang->clublist, "misc.php?action=clublist");
    }
    

	// Nur den Gruppen, den es erlaubt ist, neue Clubs hinzuzufügen, ist es erlaubt, den Link zu sehen.
	if(is_member($clublist_add_allow_groups_setting)) {
		eval("\$club_add = \"".$templates->get("clublist_add")."\";");
	}

	// FILTER
	// Filter aus den DB Einträgen generieren
     $type_query = $db->query("SELECT DISTINCT type FROM ".TABLE_PREFIX."clubs
     "); 
	 while($type_filter = $db->fetch_array($type_query)){
		$filter_type .= "<option value='{$type_filter['type']}'>{$type_filter['type']}</option>";    
	}

	$ctype = $mybb->input['type'];
	if(empty($ctype)) {
		$ctype = "%";
	}

	// Filter wird nur angezeigt, wenn aktiviert
	if($clublist_filter_setting == 1) {
		eval("\$club_filter = \"".$templates->get("clublist_filter")."\";");
	} else {
		$club_filter = "";
	}
    
	// MULTIPAGE
	$select_clubs = $db->query("SELECT * FROM ".TABLE_PREFIX . "clubs
	WHERE accepted = 1
	AND type LIKE '$ctype'
	");

	$type_url = htmlspecialchars_uni("misc.php?action=clublist&type={$ctype}");

    $count = mysqli_num_rows($select_clubs);
	$perpage = $clublist_multipage_show_setting;
	$page = intval($mybb->input['page']);
	if($page) {
		$start = ($page-1) *$perpage;
	}
	else {
		$start = 0;
		$page = 1;
	}
	$end = $start + $perpage;
	$lower = $start+1;
	$upper = $end;
	if($upper > $count) {
		$upper = $count;
	}

	if ($clublist_multipage_setting == 1) {
		$multipage = multipage($count, $perpage, $page, $type_url);
	} else {
		$multipage = "";
	}

	// ABFRAGE ALLER CLUBS - MULTIPAGE
	if ($clublist_multipage_setting == 1) {
		$query_clubs = $db->query("SELECT * FROM ".TABLE_PREFIX."clubs
		WHERE accepted != '0'
		AND type LIKE '$ctype'
		ORDER by clubname ASC
		LIMIT $start, $perpage
		");
	} 
	// ABFRAGE ALLER CLUBS - OHNE MULTIPAGE
	else {
		$query_clubs = $db->query("SELECT * FROM ".TABLE_PREFIX."clubs
		WHERE accepted != '0'
		AND type LIKE '$ctype'
		ORDER by clubname ASC
		");
	}

	// AUSGABE ALLER CLUBS
	while($club = $db->fetch_array($query_clubs)) {

		// ALLES LEER LAUFEN LASSEN
		$cid = "";
		$type = "";
		$clubname = "";
		$clubtime = "";
		$clubtext = "";
		$conductor = "";
		$createdby = "";

		// MIT INFOS FÜLLEN
		$cid = $club['cid'];
		$type = $club['type'];
		$clubname = $club['clubname'];
		$clubtime = $club['clubtime'];
		$clubtext = $club['clubtext'];
		$createdby = $club['createdby'];

		// CLUBLEITER AUSLESEN
		// Abfrage
		$query_conductor = $db->query("SELECT * FROM ".TABLE_PREFIX."users
		WHERE uid = '$club[conductor]'
		");
		// Auslese - Farbiger Name + Link
		while($cond = $db->fetch_array($query_conductor)) {
			$profilelink = format_name($cond['username'], $cond['usergroup'], $cond['displaygroup']);
			$conductor = build_profile_link($profilelink, $cond['uid']);
		}

		// MITGLIEDER DES CLUBS
		// Abfrage
		$userquery = $db->query("SELECT * FROM ".TABLE_PREFIX."clubs_user cu
        LEFT JOIN ".TABLE_PREFIX."users u
        ON (cu.uid = u.uid)
        WHERE cu.cid = '$cid'
        AND u.uid IN (SELECT uid FROM ".TABLE_PREFIX."users)
        ORDER BY u.username ASC
        ");
        
		// Leer laufen lassen
        $user_bit = "";

		// Auslese 
        while($users = $db->fetch_array($userquery)){
			// Farbiger Name + Link
            $users['username'] = format_name($users['username'], $users['usergroup'], $users['displaygroup']);
            $user = build_profile_link($users['username'], $users['uid']);

			// Position auslesen, wenn eingetragen
			if(!empty($users['position'])){
				$position = "- $users[position]";
			} else{
				$position = "";
			}

            eval("\$user_bit .= \"".$templates->get("clublist_bit_users")."\";");
        }

		// LÖSCH- & BEARBEITUNGSOPTIONEN
		// Team kann es immer die Optionen sehen
        if($mybb->usergroup['canmodcp'] == "1"){
            $edit = "» <a href=\"misc.php?action=clublist_edit&cid={$cid}\"><i class=\"fas fa-edit\" original-title=\"Club bearbeiten\"></i></a>";
            $delete = "» <a href=\"misc.php?action=clublist&delete={$cid}\"><i class=\"fas fa-trash\" original-title=\"Club löschen\"></i></a>";
        } 
		// Einsender 
		elseif ($user_id == $createdby) {
            // User darf löschen und bearbeiten
            if($clublist_delete_setting == 1 && $clublist_edit_setting == 1) {
				$edit = "» <a href=\"misc.php?action=clublist_edit&cid={$cid}\"><i class=\"fas fa-edit\" original-title=\"Club bearbeiten\"></i></a>";
				$delete = "» <a href=\"misc.php?action=clublist&delete={$cid}\"><i class=\"fas fa-trash\" original-title=\"Club löschen\"></i></a>";
            } 
			// User darf nur bearbeiten
			elseif ($clublist_delete_setting != 1 && $clublist_edit_setting == 1) {
				$edit = "» <a href=\"misc.php?action=clublist_edit&cid={$cid}\"><i class=\"fas fa-edit\" original-title=\"Club bearbeiten\"></i></a>";
				$delete = "";
			}
			// User darf nur löschen
			elseif ($clublist_delete_setting == 1 && $clublist_edit_setting != 1) {
				$edit = "";
				$delete = "» <a href=\"misc.php?action=clublist&delete={$cid}\"><i class=\"fas fa-trash\" original-title=\"Club löschen\"></i></a>";
			}
			// User darf nichts
			else {
                $edit = "";
                $delete = "";
            }
		// Gäste & alle anderen	User
        } else {
            $edit = "";
            $delete = "";
        }

		// MITGLIED WERDEN
		// Zählen, wie oft man schon Mitglied ist
        $select_user = $db->query("SELECT * FROM " . TABLE_PREFIX . "clubs_user cu
        WHERE cu.uid = '$user_id'
        ");
        // ZÄHLEN
        $count_user = mysqli_num_rows($select_user);


		// Options-Link bilden
		if(!empty($mybb->user['uid']) && is_member($clublist_member_allow_groups_setting)){
            $check = $db->fetch_field($db->query("SELECT COUNT(*) AS checked FROM ".TABLE_PREFIX."clubs_user
            WHERE uid = '$user_id'
            AND cid = '$cid'
            "), "checked");

			// BEGRENZTE MITGLIEDSCHAFT
			if ($clublist_limit_setting == 1) {
				// Unter dem Limit - kann beitreten
				if (!$check && $count_user < $clublist_limit_number_setting) {
					// Position möglich 
					if ($club['position'] == 1) {
						// POPUP-FENSTER
						eval("\$position_join .= \"".$templates->get("clublist_join_position")."\";");

						$join = "<a onclick=\"$('#position_{$cid}').modal({ fadeDuration: 250, keepelement: true, zIndex: (typeof modal_zindex !== 'undefined' ? modal_zindex : 9999) }); return false;\" style=\"cursor: pointer;\"><i class=\"fas fa-user-plus\" original-title=\"Club beitreten\"></i></a>";
						$joinlink = "{$join}<div class=\"modal\" id=\"position_{$cid}\" style=\"display: none;width:auto;\">{$position_join}</div>";
					} else {
						$joinlink = "<a href=\"misc.php?action=clublist&join={$cid}\"><i class=\"fas fa-user-plus\" original-title=\"Club beitreten\"></i></a>";
					}
				}
				// Limit erreicht - kann nicht beitreten
				elseif (!$check && $count_user == $clublist_limit_number_setting) {
					$joinlink = "";	
				}
				// Austreten
				else {
					$joinlink = "<a href=\"misc.php?action=clublist&leave={$cid}\"><i class=\"fas fa-sign-out-alt\" original-title=\"Club verlassen\"></i></a>";
				}
			} 
			// KEINE BEGRENZTE MITGLIEDSCHAFT - kann beitreten
			elseif ($clublist_limit_setting != 1) {
				// Eintreten
				if (!$check) {
					// Position möglich 
					if ($club['position'] == 1) {
						// POPUP-FENSTER
						eval("\$position_join .= \"".$templates->get("clublist_join_position")."\";");

						$join = "<a onclick=\"$('#position_{$cid}').modal({ fadeDuration: 250, keepelement: true, zIndex: (typeof modal_zindex !== 'undefined' ? modal_zindex : 9999) }); return false;\" style=\"cursor: pointer;\"><i class=\"fas fa-user-plus\" original-title=\"Club beitreten\"></i></a>";
						$joinlink = "{$join}<div class=\"modal\" id=\"position_{$cid}\" style=\"display: none;width:auto;\">{$position_join}</div>";
					} else {
						$joinlink = "<a href=\"misc.php?action=clublist&join={$cid}\"><i class=\"fas fa-user-plus\" original-title=\"Club beitreten\"></i></a>";
					}
				} 
				// Austreten
				else {
					$joinlink = "<a href=\"misc.php?action=clublist&leave={$cid}\"><i class=\"fas fa-sign-out-alt\" original-title=\"Club verlassen\"></i></a>";
				}
			} 
			else {
				$joinlink = "";
			}
	  }
        $check = "";

		
		eval("\$club_bit .= \"".$templates->get("clublist_bit")."\";");
	} 

	// MITGLIED WERDEN - MIT POSITION
    $join_position = $mybb->input['join_position'];
    if($join_position) {
        $new_record = array(
            "cid" => $db->escape_string($mybb->get_input('cid')),
            "uid" => $mybb->user['uid'],
            "position" => $db->escape_string($mybb->get_input('position')),
        );
        $db->insert_query("clubs_user", $new_record);
        redirect("misc.php?action=clublist", "{$lang->clublist_join_redirect}");
    } 

	// MITGLIED WERDEN - OHNE POSITION
    $join = $mybb->input['join'];
    if($join) {
        $new_record = array(
            "cid" => $join,
            "uid" => $mybb->user['uid'],
            "position" => $db->escape_string($mybb->get_input('position')),
        );
        $db->insert_query("clubs_user", $new_record);
        redirect("misc.php?action=clublist", "{$lang->clublist_join_redirect}");
    }
    
    // MITGLIEDSCHAFT BEENDEN
    $leave = $mybb->input['leave'];
    if($leave) {

        $uid = $mybb->user['uid'];

        $db->delete_query("clubs_user", "cid = '$leave' AND uid = '$uid'");
        redirect("misc.php?action=clublist", "{$lang->clublist_leave_redirect}");
    }
    
    // CLUB LÖSCHEN
    $delete = $mybb->input['delete'];
    if($delete) {
        // in DB Clubs löschen
        $db->delete_query("clubs", "cid = '$delete'");
        // in DB clubs_user löschen
        $db->delete_query("clubs_user", "cid = '$delete'");
        redirect("misc.php?action=clublist", "{$lang->clublist_delete_redirect}");
    }
        
	eval("\$page = \"".$templates->get("clublist")."\";");
	output_page($page);
	die();
  }

  // CLUB HINZUFÜGEN
  elseif($_POST['add_clubs']) {
	if($mybb->input['type'] == "")
	{
		error("Es muss ein Bereich ausgewählt werden!");
	}
	elseif($mybb->input['clubname'] == "")
	{
		error("Es muss ein Clubname eingetragen werden!");
	}
	elseif($mybb->input['clubtime'] == "")
	{
		error("Es muss eine Zeit eingetragen werden!");
	}
	elseif($mybb->input['clubtext'] == "")
	{
		error("Es muss eine Beschreibung eingetragen werden!");
	}else{

	 //Wenn das Team Clubs erstellt, dann werden diese sofort freigeschaltet
	 if($mybb->usergroup['canmodcp'] == '1'){
		$accepted = 1;
	 } else {
		$accepted = 0;
	 }

	 $type = $db->escape_string ($_POST['type']);
	 $clubname = $db->escape_string ($_POST['clubname']);
	 $clubtime = $db->escape_string ($_POST['clubtime']);
	 $clubtext = $db->escape_string ($_POST['clubtext']);
	 $conductor = $db->escape_string ($_POST['conductor']);
	 $position = $db->escape_string ($_POST['position']);

	 $conductor_user = get_user_by_username($conductor, array('fields' => '*'));
     $conductor = $conductor_user['uid'];

		$new_club = array(
			"type" => $type,
			"clubname" => $clubname,
			"clubtime" => $clubtime,
			"clubtext" => $clubtext,
			"conductor" => $conductor,
			"position" => $position,
			"createdby" => (int)$mybb->user['uid'],
			"accepted" => $accepted
		);

		$db->insert_query("clubs", $new_club);
		redirect("misc.php?action=clublist", "{$lang->clublist_add_redirect}");
	}
  }

  // CLUBS BEARBEITEN
  elseif($mybb->input['action'] == "clublist_edit") {

	// NAVIGATION
	if(!empty($clublist_lists_setting)){
		add_breadcrumb("Listen", "$clublist_lists_setting");
		add_breadcrumb ($lang->clublist, "misc.php?action=clublist");
		add_breadcrumb ($lang->clublist_edit, "misc.php?action=clublist_edit");
    } else{
		add_breadcrumb ($lang->clublist, "misc.php?action=clublist");
		add_breadcrumb ($lang->clublist_edit, "misc.php?action=clublist_edit");
    }

	$cid =  $mybb->get_input('cid', MyBB::INPUT_INT);

	$edit_query = $db->query("
	SELECT * FROM ".TABLE_PREFIX."clubs
	WHERE cid = '".$cid."'
	");

	$edit = $db->fetch_array($edit_query);

	// Alles leer laufen lassen
	$cid = "";
	$type = "";
	$clubname = "";
	$clubtime = "";
	$clubtext = "";
	$position = "";

	// Füllen wir mal alles mit Informationen
	$cid = $edit['cid'];
	$type = $edit['type'];
	$clubname = $edit['clubname'];
	$clubtime = $edit['clubtime'];
	$clubtext = $edit['clubtext'];
	$position = $edit['position'];

	// Leiter 
	$conductor_user = get_user($edit['conductor'], array('fields' => '*'));
    $edit['conductor'] = $conductor_user['username'];

	// Position
	if ($position == '1') {
		$position_word = "Position möglich";
	} else {
		$position_word = "Position nicht möglich";
	}

	//Der neue Inhalt wird nun in die Datenbank eingefügt bzw. die alten Daten überschrieben.
	if($_POST['edit_clubs']){

		$cid = $mybb->input['cid'];
		$type = $db->escape_string ($_POST['type']);
		$clubname = $db->escape_string ($_POST['clubname']);
		$clubtime = $db->escape_string ($_POST['clubtime']);
		$clubtext = $db->escape_string ($_POST['clubtext']);
		$conductor = $db->escape_string ($_POST['conductor']);
		$position = $db->escape_string ($_POST['position']);

		$conductor_user = get_user_by_username($conductor, array('fields' => '*'));
		$conductor = $conductor_user['uid'];

		$edit_club = array(
			"type" => $type,
			"clubname" => $clubname,
			"clubtime" => $clubtime,
			"clubtext" => $clubtext,
			"conductor" => $conductor,
			"position" => $position,
		);

		$db->update_query("clubs", $edit_club, "cid = '".$cid."'");
		redirect("misc.php?action=clublist", "{$lang->clublist_edit_redirect}");
	}


   // TEMPLATE FÜR DIE SEITE
   eval("\$page = \"".$templates->get("clublist_edit")."\";");
   output_page($page);
   die();
 }

}

// MOD-CP
function clublist_modcp_nav()
{
    global $db, $mybb, $templates, $theme, $header, $headerinclude, $footer, $lang, $modcp_nav, $nav_clublist;
    
    $lang->load('clublist');

    eval("\$nav_clublist = \"".$templates->get ("clublist_modcp_nav")."\";");
}

function clublist_modcp() {
    global $mybb, $templates, $lang, $header, $headerinclude, $footer, $db, $page, $modcp_nav, $clublist_modcp_bit;

	// SPRACHDATEI
	$lang->load('clublist');

	if($mybb->get_input('action') == 'clublist') {

	 // Add a breadcrumb
	 add_breadcrumb($lang->nav_modcp, "modcp.php");
	 add_breadcrumb($lang->clublist_modcp, "modcp.php?action=clublist");

	 // CLUBS ABFRAGEN
	 $mod = $db->query("
	 SELECT * FROM ".TABLE_PREFIX."clubs
	 WHERE accepted = '0'
	 ORDER BY clubname ASC
	 ");

	 // WOHNORT AUSLESEN
     while($modcp = $db->fetch_array($mod)) {
   
        // Alles leer laufen lassen
         $cid = "";
		 $type = "";
		 $clubname = "";
		 $clubtime = "";
		 $clubtext = "";
		 $conductor = "";
		 $position = "";
   
         // Füllen wir mal alles mit Informationen
         $cid = $modcp['cid'];
         $type = $modcp['type'];
         $clubname = $modcp['clubname'];
         $clubtime = $modcp['clubtime'];
         $clubtext = $modcp['clubtext'];
   
         // User der das eingesendet hat
         $modcp['createdby'] = htmlspecialchars_uni($modcp['createdby']);
         $user = get_user($modcp['createdby']);
         $user['username'] = htmlspecialchars_uni($user['username']);
         $createdby = build_profile_link($user['username'], $modcp['createdby']);

		 
         // Leiter auslesen
		 if(!empty($modcp['conductor'])){
         $modcp['conductor'] = htmlspecialchars_uni($modcp['conductor']);
         $user = get_user($modcp['conductor']);
         $user['username'] = htmlspecialchars_uni($user['username']);
         $conductor = build_profile_link($user['username'], $modcp['conductor']);
		 } else {
			$conductor = "kein Leiter aktuell"; 
		 }

		 // Position
		 if ($modcp['position'] == '1') {
			$position = "Position möglich";
		} else {
			$position = "Position nicht möglich";
		}
   
         eval("\$clublist_modcp_bit .= \"".$templates->get("clublist_modcp_bit")."\";");
       }
	  
	   // Der Club wird vom Team abgelehnt 
	   $dec = $mybb->input['declined'];
	   if($dec){

        // MyALERTS STUFF
        $query_alert = $db->simple_select("clubs", "*", "cid = '{$dec}'");
        while ($alert_del = $db->fetch_array ($query_alert)) {
           if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
               $user = get_user($alert['createdby']);
               $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('clublist_declined');
                if ($alertType != NULL && $alertType->getEnabled()) {
                   $alert = new MybbStuff_MyAlerts_Entity_Alert((int)$alert_del['createdby'], $alertType, (int)$dec);
                   $alert->setExtraDetails([
                       'username' => $user['username'],
                       'clubname' => $alert_del['clubname'],
                       'type' => $alert_del['type']
                   ]);
                   MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
               }
           }
       }

      $db->delete_query("clubs", "cid = '$del'");
      redirect("modcp.php?action=clublist", "{$lang->clublist_modcp_declined_redirect}");    
     }

     // Der Club wurde vom Team angenommen 
     if($acc = $mybb->input['accepted']){

        // MyALERTS STUFF
        $query_alert = $db->simple_select("clubs", "*", "cid = '{$acc}'");
        while ($alert_acc = $db->fetch_array ($query_alert)) {
           if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
               $user = get_user($alert['createdby']);
               $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('clublist_accepted');
                if ($alertType != NULL && $alertType->getEnabled()) {
                   $alert = new MybbStuff_MyAlerts_Entity_Alert((int)$alert_acc['createdby'], $alertType, (int)$acc);
                   $alert->setExtraDetails([
                       'username' => $user['username'],
                       'clubname' => $alert_acc['clubname'],
                       'type' => $alert_acc['type']
                   ]);
                   MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
               }
           }
       }

        $db->query("UPDATE ".TABLE_PREFIX."clubs SET accepted = 1 WHERE cid = '".$acc."'");
        redirect("modcp.php?action=clublist", "{$lang->clublist_modcp_accepted_redirect}");    
     }
		 

	 // TEMPLATE FÜR DIE SEITE
	 eval("\$page = \"".$templates->get("clublist_modcp")."\";");
	 output_page($page);
	 die();
	}
}

function clublist_myalert_alerts() {
	global $mybb, $lang;
	$lang->load('clublist');

    // CLUB ANNEHMEN
    /**
	 * Alert formatter for my custom alert type.
	 */
	class MybbStuff_MyAlerts_Formatter_ClubAcceptedFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
	{
	    /**
	     * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
	     *
	     * @return string The formatted alert string.
	     */
	    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
	    {
			global $db;
			$alertContent = $alert->getExtraDetails();
            $userid = $db->fetch_field($db->simple_select("users", "uid", "username = '{$alertContent['username']}'"), "uid");
            $user = get_user($userid);
            $alertContent['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
	        return $this->lang->sprintf(
	            $this->lang->clublist_accepted,
				$outputAlert['from_user'],
				$alertContent['username'],
	            $outputAlert['dateline'],
				$alertContent['clubname'],
				$alertContent['type']
	        );
	    }

	    /**
	     * Init function called before running formatAlert(). Used to load language files and initialize other required
	     * resources.
	     *
	     * @return void
	     */
	    public function init()
	    {
	        if (!$this->lang->clublist) {
	            $this->lang->load('clublist');
	        }
	    }

	    /**
	     * Build a link to an alert's content so that the system can redirect to it.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
	     *
	     * @return string The built alert, preferably an absolute link.
	     */
	    public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
	    {
	        return $this->mybb->settings['bburl'] . '/misc.php?action=clublist';
	    }
	}

	if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

		if (!$formatterManager) {
			$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}

		$formatterManager->registerFormatter(
				new MybbStuff_MyAlerts_Formatter_ClubAcceptedFormatter($mybb, $lang, 'clublist_accepted')
		);
	}


	// CLUB ABLEHNEN
    /**
	 * Alert formatter for my custom alert type.
	 */
	class MybbStuff_MyAlerts_Formatter_ClubDeclinedFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
	{
	    /**
	     * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
	     *
	     * @return string The formatted alert string.
	     */
	    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
	    {
			global $db;
			$alertContent = $alert->getExtraDetails();
            $userid = $db->fetch_field($db->simple_select("users", "uid", "username = '{$alertContent['username']}'"), "uid");
            $user = get_user($userid);
            $alertContent['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
	        return $this->lang->sprintf(
	            $this->lang->clublist_declined,
				$outputAlert['from_user'],
				$alertContent['username'],
	            $outputAlert['dateline'],
				$alertContent['clubname'],
				$alertContent['type']
	        );
	    }

	    /**
	     * Init function called before running formatAlert(). Used to load language files and initialize other required
	     * resources.
	     *
	     * @return void
	     */
	    public function init()
	    {
	        if (!$this->lang->clublist) {
	            $this->lang->load('clublist');
	        }
	    }

	    /**
	     * Build a link to an alert's content so that the system can redirect to it.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
	     *
	     * @return string The built alert, preferably an absolute link.
	     */
	    public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
	    {
	        return $this->mybb->settings['bburl'] . '/misc.php?action=clublist';
	    }
	}

	if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

		if (!$formatterManager) {
			$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}

		$formatterManager->registerFormatter(
				new MybbStuff_MyAlerts_Formatter_ClubDeclinedFormatter($mybb, $lang, 'clublist_declined')
		);
	} 
}

// ANZEIGE IM PORFIL
function clublist_memberprofile() {

    global $db, $mybb, $lang, $templates, $theme, $memprofile, $member_profile_clubs, $member_clubs_bit, $conductor_clubs_bit;

	// SPRACHDATEI LADEN
    $lang->load("clublist");

    $uid = $mybb->get_input('uid', MyBB::INPUT_INT);

	// ABFRAGE - MITGLIEDSCHAFTEN
	$profile_query = $db->query("SELECT * FROM ".TABLE_PREFIX."clubs_user cu
        LEFT JOIN ".TABLE_PREFIX."clubs c
        ON (cu.cid = c.cid) 
        WHERE cu.uid = '".$uid."'
    ");

	   // Keine Mitgliedschaten  
	   eval("\$member_clubs_bit_none = \"".$templates->get("clublist_memberprofile_bit_none")."\";");

	// AUSGABE - MITGLIEDSCHAFTEN
	while($prof = $db->fetch_array($profile_query)){

		$member_clubs_bit_none = "";

		// Alles leer laufen lassen
		$cid = "";
		$type = "";
		$clubname = "";
		$clubtime = "";
  
		// Füllen wir mal alles mit Informationen
		$cid = $prof['cid'];
		$type = $prof['type'];
		$clubname = $prof['clubname'];
		$clubtime = $prof['clubtime'];

		// Position auslesen, wenn eingetragen
		$pos_query = $db->query("SELECT * FROM ".TABLE_PREFIX."clubs_user
        WHERE cid = '$cid'
		");
		while($pos = $db->fetch_array($pos_query)){
			if(!empty($pos['position'])){
				$position = "- $pos[position]";
			} else{
				$position = "";
			}
		}

		eval("\$member_clubs_bit .= \"".$templates->get("clublist_memberprofile_bit")."\";");
	   }

	   // ABFRAGE - LEITUNG
	   $conductor_query = $db->query("SELECT * FROM ".TABLE_PREFIX."clubs 
	   WHERE conductor = '".$uid."'
	   AND accepted = '1'
	   ");

	   // Keine Leitungsposition
	   eval("\$conductor_clubs_bit_none = \"".$templates->get("clublist_memberprofile_conductor_bit_none")."\";");

	   // AUSGABE - MITGLIEDSCHAFTEN
	   while($prof = $db->fetch_array($conductor_query)){

		$conductor_clubs_bit_none = "";

		// Alles leer laufen lassen
		$cid = "";
		$type = "";
		$clubname = "";
		$clubtime = "";

		// Füllen wir mal alles mit Informationen
		$cid = $prof['cid'];
		$type = $prof['type'];
		$clubname = $prof['clubname'];
		$clubtime = $prof['clubtime'];

		eval("\$conductor_clubs_bit .= \"".$templates->get("clublist_memberprofile_conductor_bit")."\";");
	 }

	   eval("\$member_profile_clubs .= \"".$templates->get("clublist_memberprofile")."\";");
}
