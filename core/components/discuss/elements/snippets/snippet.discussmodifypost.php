<?php
/**
 *
 * @package discuss
 */
$discuss = $modx->getService('discuss','Discuss',$modx->getOption('discuss.core_path',null,$modx->getOption('core_path').'components/discuss/').'model/discuss/',$scriptProperties);
if (!($discuss instanceof Discuss)) return '';
$discuss->initialize($modx->context->get('key'));

if (empty($_REQUEST['post'])) { $modx->sendErrorPage(); }
$post = $modx->getObject('disPost',$_REQUEST['post']);
if ($post == null) { $modx->sendErrorPage(); }

/* setup defaults */
$properties = $post->toArray();

/* get thread root */
$thread = $post->getThreadRoot();
if ($thread == null) $modx->sendErrorPage();
$properties['thread'] = $thread->get('id');

/* get board breadcrumb trail */
$c = $modx->newQuery('disBoard');
$c->innerJoin('disBoardClosure','Ancestors');
$c->where(array(
    'Ancestors.descendant' => $post->get('board'),
));
$c->sortby('Ancestors.depth','ASC');
$ancestors = $modx->getCollection('disBoard',$c);

/* build breadcrumbs */
$trail = array();
$trail[] = array(
    'url' => $modx->makeUrl($modx->getOption('discuss.board_list_resource')),
    'text' => $modx->lexicon('discuss.home'),
);
foreach ($ancestors as $ancestor) {
    $trail[] = array(
        'url' => '[[~[[++discuss.board_resource]]? &board=`'.$ancestor->get('id').'`]]',
        'text' => $ancestor->get('name'),
    );
}
$trail[] = array(
    'text' => $modx->lexicon('discuss.modify_post_header',array('post' => $post->get('title'))),
    'active' => true,
);
$trail = $modx->hooks->load('breadcrumbs',array_merge($scriptProperties,array(
    'items' => &$trail,
)));
$properties['trail'] = $trail;

/* if POST, process new thread request */
if (!empty($_POST)) {
    $modx->toPlaceholders($_POST,'post');
    include $discuss->config['processorsPath'].'web/post/modify.php';
}


/* get thread */
$props = array_merge($scriptProperties,array(
    'post' => &$post,
    'thread' => &$thread,
));
$properties['thread_posts'] = $modx->hooks->load('post/getthread',$props);


/* output form to browser */
$modx->regClientStartupScript($discuss->config['jsUrl'].'web/dis.post.modify.js');
$modx->setPlaceholder('discuss.error_panel',$discuss->getChunk('disError'));
$modx->setPlaceholder('discuss.post',$post->get('title'));

return $discuss->output('thread/modify',$properties);