<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
// Basics
require 'config.php';
require 'function/string.php';
require 'function/time.php';
require 'function/javascript.php';
require 'function/html.php';
require 'function/xml.php';
require 'function/misc.php';
require 'function/image.php';
require 'function/mail.php';
require 'functions.php';
// Library
require 'database.php';
require 'locale.php';
require 'auth.php';
// Model
require 'model/blog.service.php';
require 'model/blog.blogSetting.php';
require 'model/blog.user.php';
require 'model/common.paging.php';
require 'model/common.setting.php';
require 'model/common.plugin.php';
require 'model/reader.common.php';
// View
require 'view/html.php';
require 'view/pages.php';
require 'view/ownerView.php';
require 'view/paging.php';
require 'view/view.php';

// Initializing environment.
require 'initialize.php';
require 'plugins.php';

header('Content-Type: text/html; charset=utf-8');
// Check access control list
requireOwnership();
require 'pageACL.php';
?>
