<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
$IV = array(
	'GET' => array(
		'write' => array(array('0', '1')),
		'comment' => array(array('0', '1'))
	)
);
require ROOT . '/library/includeForBlogOwner.php';
requireStrictRoute();
if (setGuestbook($blogid, $_GET['write'], $_GET['comment'])) {
	Respond::ResultPage(0);
}
Respond::ResultPage( - 1);
?>
