<?php
/**
 * Discuss
 *
 * Copyright 2010-11 by Shaun McCormick <shaun@modx.com>
 *
 * This file is part of Discuss, a native forum for MODx Revolution.
 *
 * Discuss is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * Discuss is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Discuss; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package discuss
 */
$mtime = microtime();
$mtime = explode(' ', $mtime);
$mtime = $mtime[1] + $mtime[0];
$tstart = $mtime;
set_time_limit(0);

/* override with your own defines here (see build.config.sample.php) */
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.core.php';
require_once MODX_CORE_PATH.'config/'.MODX_CONFIG_KEY.'.inc.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
$modx= new modX();
$modx->initialize('mgr');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget('ECHO');

/* load Discuss */
/** @var $discuss Discuss */
$discuss = $modx->getService('discuss','Discuss',$modx->getOption('discuss.core_path',null,$modx->getOption('core_path').'components/discuss/').'model/discuss/');
if (!($discuss instanceof Discuss)) return '';

define('DISCUSS_IMPORT_MODE',true);

/* setup mem limits */
ini_set('memory_limit','1024M');
set_time_limit(0);
@ob_end_clean();
echo '<pre>';

$modx->user = $modx->getObject('modUser',1);

$discuss->initialize('web');

function disParseCodeCallback($matches) {
    $str = str_replace(array(
        '<br />',
        '&nbsp;',
        '&#039;',
    ),array(
        "\n",
        ' ',
        "'",
    ),$matches[0]);
    return html_entity_decode($str,ENT_COMPAT,'UTF-8');
}


/* load and run importer */
if ($discuss->loadImporter('disSmfImport')) {
    $c = $modx->newQuery('disPost');
    $c->select($modx->getSelectColumns('disPost','disPost'));
    $c->where(array(
        'message:LIKE' => '%[code%',
    ));
    $posts = $modx->getIterator('disPost',$c);
    /** @var disPost $post */
    foreach ($posts as $post) {
        $discuss->import->log('Fixing br tags for: '.$post->get('title'));
        $message = $post->get('message');
        $message = preg_replace_callback("#\[code\](.*?)\[/code\]#si",'disParseCodeCallback',$message);
        $message = preg_replace_callback("#\[code=[\"']?(.*?)[\"']?\](.*?)\[/code\]#si",'disParseCodeCallback',$message);
        $post->set('message',$message);
        $post->save();
    }

} else {
    $modx->log(xPDO::LOG_LEVEL_ERROR,'Failed to load Import class.');
}

$mtime= microtime();
$mtime= explode(" ", $mtime);
$mtime= $mtime[1] + $mtime[0];
$tend= $mtime;
$totalTime= ($tend - $tstart);
$totalTime= sprintf("%2.4f s", $totalTime);

$modx->log(modX::LOG_LEVEL_INFO,"\nExecution time: {$totalTime}\n");

exit ();
@session_write_close();
die();

/**
 * disBoard - smf_boards
 * disCategory - smf_categories
 * disPost - smf_messages (smf_topics?)
 * disPostAttachment - smf_attachments
 * modUserGroup/disUserGroupProfile - smf_membergroups
 * disModerator - smf_moderators
 * disUser/modUser - smf_members
 */