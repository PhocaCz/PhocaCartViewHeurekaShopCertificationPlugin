<?php
spl_autoload_register(function($className) {
	if($className[0] == '\\') {
		$className = substr($className, 1);
	}

	// Leave if class should not be handled by this autoloader
	if(strpos($className, 'Heureka\\ShopCertification') !== 0) return;

	$classPath = strtr(substr($className, strlen('Heureka')), '\\', '/') . '.php';


	if(file_exists(JPATH_PLUGINS.'/pcv/heureka_cz_shop_certification/helpers/src'. $classPath)) {
		require(JPATH_PLUGINS.'/pcv/heureka_cz_shop_certification/helpers/src'. $classPath);
	}
});
?>
