<?php
/* @package Joomla
 * @copyright Copyright (C) Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @extension Phoca Extension
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

defined('_JEXEC') or die;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;

jimport( 'joomla.plugin.plugin' );
jimport( 'joomla.filesystem.file');
jimport( 'joomla.html.parameter' );


JLoader::registerPrefix('Phocacart', JPATH_ADMINISTRATOR . '/components/com_phocacart/libraries/phocacart');

require_once JPATH_PLUGINS.'/pcv/heureka_cz_shop_certification/helpers/autoload.php';

class plgPCVHeureka_cz_shop_certification extends CMSPlugin
{
	function __construct(& $subject, $config) {
		parent :: __construct($subject, $config);
		$this->loadLanguage();
		$lang = Factory::getLanguage();
		//$lang->load('com_phocacart.sys');
		$lang->load('com_phocacart');
	}

	public function onPCVonInfoViewDisplayContent($context, &$infoData, &$infoAction, $eventData) {

		$p = [];
		$p['heureka_api_key'] = $this->params->get('heureka_api_key', '');

		if (!isset($infoData['user_id'])) { $infoData['user_id'] = 0;}

		$order = PhocacartOrder::getOrder($infoData['order_id'], $infoData['order_token'], $infoData['user_id']);

		// $infoAction == 5 means that the order is cancelled, so no conversion
		if (isset($order['id']) && (int)$order['id'] > 0 && $infoAction != 5) {
			$orderProducts = PhocacartOrder::getOrderProducts($order['id']);
			$orderUser = PhocacartOrder::getOrderUser($order['id']);
			//$orderTotal = PhocacartOrder::getOrderTotal($order['id'], ['sbrutto', 'snetto', 'pbrutto', 'pnetto']);

			if (!empty($orderProducts)) {

				try {

					// Use your own API key here. And keep it secret!
					$apiKey = $p['heureka_api_key'];
					$options = [
						// Use \Heureka\ShopCertification::HEUREKA_SK if your e-shop is on heureka.sk
						'service' => \Heureka\ShopCertification::HEUREKA_CZ,
					];

					$shopCertification = new \Heureka\ShopCertification($apiKey, $options);

					// Set customer email - it is MANDATORY.
					$email = false;

					if (isset($orderUser[0]['email']) && $orderUser[0]['email'] != '') {
						$email = $orderUser[0]['email'];
					} else if (isset($orderUser[1]['email']) && $orderUser[1]['email'] != '') {
						$email = $orderUser[1]['email'];
					} else if (isset($orderUser[0]['email_contact']) && $orderUser[0]['email_contact'] != '') {
						$email = $orderUser[0]['email_contact'];
					} else if (isset($orderUser[1]['email_contact']) && $orderUser[1]['email_contact'] != '') {
						$email = $orderUser[1]['email_contact'];
					}

					$shopCertification->setEmail($email);

					// Set order ID - it helps you track your customers' orders in Heureka shop administration.
					$shopCertification->setOrderId((int)$order['id']);

					// Add products using ITEM_ID (your products ID)
					foreach ($orderProducts as $k => $v) {
						$shopCertification->addProductItemId((int)$v['id']);
					}

					// And finally send the order to our service.
					$shopCertification->logOrder();

					// Everything went well - we are done here.

				} catch (ZboziKonverzeException | RequesterException $e) {
					// zalogování případné chyby
					$ip = PhocacartUtils::getIp();
					PhocacartLog::add(2, 'Shop Certification error - Heureka.cz', (int)$order['id'], 'IP: '. $ip.', Order ID: '.(int)$order['id']. ', Message: '.$e->getMessage());
				} catch(Exception $e) {
    				// Handle the general case
					$ip = PhocacartUtils::getIp();
					PhocacartLog::add(2, 'Shop Certification error - Heureka.cz', (int)$order['id'], 'IP: '. $ip.', Order ID: '.(int)$order['id']. ', Message: '.$e->getMessage());
				}

			}
		}

		/*
		$output = array();
		$output['content'] = '';

		return $output;
		*/
	}

}
?>
