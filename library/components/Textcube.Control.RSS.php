<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
class RSS {
	function refresh() {
		if( file_exists(ROOT . "/cache/rss/".getBlogId().".xml") ) {
			@unlink(ROOT . "/cache/rss/".getBlogId().".xml");
		}
	}
}
?>
