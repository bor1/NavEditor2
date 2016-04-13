<?php


function createSubMenu($num)   {
    global $ne2_menu;
    global $g_current_user_name;
//    global $is_admin;

    $actualPage = $_SERVER['PHP_SELF'];
//    $actualPath = $_SERVER['REQUEST_URI'];
    $actualPageName = basename($actualPage);

    $link = '';
    $um = new UserMgmt();
	foreach ($ne2_menu as $i => $v) {
    		$class = '';
    		$attribute = '';
    		$desc = '';
    		$key = $v['id'];

    		if ($num == $v['up']) {
                        if($um ->isAllowAccess($v['id'], $g_current_user_name)){
				if ($actualPageName == $v['link']) {
					$class .= 'current';
				}
				if (isset($v['addclass'])) {
					$class .= ' '.$v['addclass'];
				}
				if ($v['sub'] == 1) {
					$class .=  ' sf-with-ul';
				}
				if (isset($v['attribut'])) {
					$attribute = $v['attribut'];
				}
				if (isset($v['desc']) && ($v['desc'])) {
						$desc = 'title="'.$v['desc'].'"';
				}

				$link .= '<li';
				if ($class) {
					$link .= " class=\"$class\"";
				}
				$link .= ">";
	//			if ($actualPageName != $v['link']) {
						$link .= '<a '.$desc.' '.$attribute.'  href="'.$v['link'].'">';
	//			}
				$link .= $v['title'];
			      if ($v['sub'] == 1) {
					$link .= '<span class="sf-sub-indicator"> &#187;</span>';
				}
//				if ($actualPageName != $v['link']) {
						$link .= '</a>';
//				}
			      if ($v['sub'] == 1) {
			      	$link .= "<ul class=\"submenu\">\n";
			      	$link .= createSubMenu($key);
			      	$link .= "</ul>\n";

				}

	   		      $link .= "</li>\n";

			}
		}
    }
    return $link;

}


?>



<script src="js/jquery.hoverIntent.minified.js"></script>
<script src="js/superfish.js"></script>

<script>

 $(document).ready(function(){
        $("ul.sf-menu").superfish({
            delay: 10,
            speed: 'fast'
        });
    });

</script>
<nav id="mainnav">
	<ul class=" sf-menu">
		<?php echo createSubMenu(0);    ?>
	</ul>
</nav>
<div class="sitelink">
	<a id="returnToSite" href="/" target="_blank">Zur&uuml;ck zur Website</a>
</div>
