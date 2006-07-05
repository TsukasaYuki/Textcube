<?
/*--------- Debugging environment -----------*/
global $debug, $debug_file, $blogapi_dir;
$debug = 0; /* DEBUGLOG */
$debug_file = "../../plugins/BlogAPI/.htdebug.log";
$blogapi_dir = dirname( __FILE__ );

if( $debug )
{
	global $debugfd, $debug_file;
	$debugfd = fopen( $debug_file, "a" );
}
else
{
	if( file_exists( $debug_file ) )
	{
		unlink( $debug_file );
	}
}

function DEBUG( $str, $internal = false)
{
	global $debug, $debugfd;
	if( !$debug )
	{
		return;
	}
	if( $internal )
	{
		$str = var_export( $str, true );
	}
	fputs( $debugfd, $str );
}

/*--------- Tatter tools Core component load   -----------*/

function includeOnce($name){
	global $blogapi_dir;
	if(!ereg('^[[:alnum:]]+[[:alnum:].]+$',$name))
		return ;
	if( TATTERTOOLS_VERSION < "1.0.6" && file_exists( $blogapi_dir . "/$name.php" ) )
	{
		include_once ( $blogapi_dir . "/$name.php");
	}
	else
	{
		include_once ( $blogapi_dir . "/../../components/$name.php");
	}
}
includeOnce( "Eolin.PHP.Core" );
includeOnce( "Eolin.PHP.XMLStruct" );
includeOnce( "Eolin.PHP.XMLTree" );
includeOnce( "Eolin.PHP.XMLRPC" );
includeOnce( "Tattertools.Core" );
includeOnce( "Tattertools.Control.Auth" );
includeOnce( "Tattertools.Data.Post" );
includeOnce( "Tattertools.Data.Category" );

/*--------- Tatter tools Core component load   -----------*/
DEBUG( "\nTRANSACTION ---------- start   ----------- [" . date("r") . "]\n");
DEBUG( "Agent: " . $_SERVER["HTTP_USER_AGENT"] );
DEBUG( "\nTRANSACTION ---------- request -----------\n" );
DEBUG( $GLOBALS['HTTP_RAW_POST_DATA'] );
DEBUG( "\nTRANSACTION ---------- api -----------\n");


/*--------- API Callbacks -----------*/


/*--------- Basic functions -----------*/

function _get_canonical_id( $id )
{
	global $blogapi_dir;
	$alias_file = $blogapi_dir . "/.htaliases";
	if( !file_exists( $alias_file ) )
	{
		return $id;
	}
	$fd = fopen( $alias_file, "r" );
	$canon = $id;
	while( !feof($fd) )
	{
		$line = fgets( $fd, 1024 );
		if( substr($line,0,1) == "#" )
		{
			continue;
		}
		$match = preg_split( '/( |\t|\r|\n)+/', $line );
		if( $id == $match[0] )
		{
			$canon = $match[1];
			break;
		}
	}
	fclose( $fd );
	return $canon;
}

function _login( $id, $password )
{
	DEBUG( "\n_login: ID: $id, PASSWORD: $password\n" );

	$auth = new Auth;
	if( !$auth->login( $id, $password ) )
	{
		$canon_id = _get_canonical_id($id);
		if( !$auth->login( $canon_id, $password ) )
		{
			DEBUG( "_login: Authentication failed.\n" );
			return new XMLRPCFault( 1, "Authentication failed: $id($canon_id)" );
		}
	}
	DEBUG( "_login: Authenticated.\n" );
	return false;
}

function _utf8_substr($str,$start) 
{ 
	preg_match_all("/./u", $str, $ar); 

	if(func_num_args() >= 3) { 
		$end = func_get_arg(2); 
		return join("",array_slice($ar[0],$start,$end)); 
	} else { 
		return join("",array_slice($ar[0],$start)); 
	} 
} 

function _get_title( $content )
{
	if( preg_match( "{<title>(.+)?</title>}", $content, $match ) )
	{
		return $match[1];
	}
	$title = preg_replace( "{<.*?>}", "", $content);
	$title = _utf8_substr( $title, 0, 40 );
	return $title;
}

function _escape_content( $content )
{
	$content = str_replace( "\r", '', $content );
	return htmlspecialchars($content);
}

function _timestamp( $date8601 )
{
	if( substr( $date8601, 8,1 ) != "T" )
	{
		return $date8601;
	}
	return Timezone::getOffset() + 
		mktime( 
			substr( $date8601, 9, 2 ),
			substr( $date8601, 12, 2 ),
			substr( $date8601, 15, 2 ),
			substr( $date8601, 4, 2 ),
			substr( $date8601, 6, 2 ),
			substr( $date8601, 0, 4 ) );
}

function _dateiso8601( $timestamp )
{
	DEBUG( "Enter: " . __FUNCTION__ . "\n" );
	$params = func_get_args();
	return gmstrftime( "%Y%m%dT%H:%M:%S", $timestamp );
}


function send_failure( $msg )
{
	print(  "<methodResponse>\n" .
			"<fault><value><struct>\n" .
			"<member>\n" .
			"<name>faultCode</name>\n" .
			"<value><int>1</int></value>\n" .
			"</member>\n" .
			"<member>\n" .
			"<name>faultString</name>\n" .
			"<value><string>" . _escape_content($msg) . "</string></value>\n" .
			"</member>\n" .
			"</struct></value></fault>\n" .
			"</methodResponse>\n" );
}

/*--------- API main ---------------*/
function BlogAPI()
{
	include "blogger.php";
	include "metaweblog.php";

	$xml = $GLOBALS['HTTP_RAW_POST_DATA'];
	if( $debug == 11 )
	{
		$f = fopen( "test.xml", "r" );
		$xml = fread( $f, 32768 );
		fclose( $f );
	}

	$functions = array(
		"blogger.getUsersBlogs",
		"blogger.newPost",
		"blogger.editPost",
		"blogger.getTemplate",
		"blogger.getRecentPosts",
		"blogger.deletePost", 
		"blogger.getPost", 
		"metaWeblog.newPost",
		"metaWeblog.getPost",
		"metaWeblog.getCategories",
		"metaWeblog.getRecentPosts",
		"metaWeblog.editPost",
		"metaWeblog.newMediaObject",
		"mt.getPostCategories",
		"mt.setPostCategories",
		"mt.getCategoryList" );

	$xmlrpc = new XMLRPC;

	foreach( $functions as $func )
	{
		$callback = str_replace( ".", "_", $func );
		$xmlrpc->registerMethod( $func, $callback );
	}

	$xmlrpc->receive( $xml );

	if( $debug == 11 )
	{
		print($xml);
	}

	if( $debug )
	{
		fclose( $debugfd );
	}

	DEBUG( "\nTRANSACTION ---------- end  -----------\n");

	if(!headers_sent())
	{
		send_failure( $xml );
	}
	return "";
}

function AddRSD($target)
{
	global $hostURL, $blogURL;
	$target .= '<link rel="EditURI" type="application/rsd+xml" title="RSD" href="'.$hostURL.$blogURL.'/plugin/BlogAPI/rsd" />'.CRLF;
	return $target;
}

function SendRSD()
{
	global $hostURL, $blogURL;
	$homeurl = $hostURL.$blogURL;
	$apiurl = $homeurl . "/plugin/BlogAPI";

	print( '<?xml version="1.0" ?> 
<rsd version="1.0">
    <service>
        <engineName>Tattertools</engineName> 
        <engineLink>http://www.tattertools.com/</engineLink>
        <homePageLink>' . $homeurl . '</homePageLink>
        <apis>
                <api name="MetaWeblog" preferred="true" apiLink="' . $apiurl . '" blogID="" />
                <api name="Blogger" preferred="false" apiLink="' . $apiurl . '" blogID="" />
        </apis>
    </service>
</rsd>' );
}

function BlogAPITest()
{
	global $debug,$service, $blog;
	if( !$debug )
	{
		print( "<b>Set \"\$debug = 1;\" in " . __FILE__ );
		return;
	}
	print( "<pre>" );
	print( dirname(__FILE__) . "\n" );
	print( "Test page for checking.\n" );
	print( "Tatter tools version: " . TATTERTOOLS_VERSION . "\n");
	print( "Tatter tools root: " . ROOT . "\n");
	print( "Included " );
	print_r( get_included_files() );
	print( "</pre>" );
}

function BlogAPIAtom()
{
	includeOnce( "atom" );
	DoAtom();
}
?>
