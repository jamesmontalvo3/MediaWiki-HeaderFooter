<?php
/**
 * @package HeaderFooter
 */
class HeaderFooter
{
	/**
	 * Main Hook
	 */
	public static function hOutputPageParserOutput( &$op, $parserOutput ) {

		$action = $op->parserOptions()->getUser()->getRequest()->getVal("action");
		if ( ($action == 'edit') || ($action == 'submit') || ($action == 'history') ) {
			return true;
		}

		global $wgTitle;

		$ns = $wgTitle->getNsText();
		$name = $wgTitle->getPrefixedDBKey();

		$text = $parserOutput->getText();

		$categories = array_keys( $wgTitle->getParentCategories() ) ;
		$catheader = "" ;
		$catfooter = "" ;
                foreach( $categories as &$cat ) {
                        $catname = substr( $cat, strpos( $cat, ":" ) + 1 );
			$catheader = $catheader . self::conditionalInclude( $text, '__NOCATHEADER__', 'hf-catheader', $catname );
			$catfooter = self::conditionalInclude( $text, '__NOCATFOOTER__', 'hf-catfooter', $catname ) . $catfooter;
		}

		$nsheader = self::conditionalInclude( $text, '__NONSHEADER__', 'hf-nsheader', $ns );
		$header   = self::conditionalInclude( $text, '__NOHEADER__',   'hf-header', $name );
		$footer   = self::conditionalInclude( $text, '__NOFOOTER__',   'hf-footer', $name );
		$nsfooter = self::conditionalInclude( $text, '__NONSFOOTER__', 'hf-nsfooter', $ns );

		$parserOutput->setText( $nsheader . $catheader . $header . $text . $footer . $catfooter . $nsfooter );

		global $egHeaderFooterEnableAsyncHeader, $egHeaderFooterEnableAsyncFooter;
		if ( $egHeaderFooterEnableAsyncFooter || $egHeaderFooterEnableAsyncHeader ) {
			$op->addModules( 'ext.headerfooter.dynamicload' );
		}

		return true;
	}

	/**
	 * Verifies & Strips ''disable command'', returns $content if all OK.
	 */
	static function conditionalInclude( &$text, $disableWord, $class, $unique ) {

		// is there a disable command lurking around?
		$disable = strpos( $text, $disableWord ) !== false;

		// if there is, get rid of it
		// make sure that the disableWord does not break the REGEX below!
		$text = preg_replace('/'.$disableWord.'/si', '', $text );

		// if there is a disable command, then don't return anything
		if ( $disable ) {
			return null;
		}

		$msgId = "$class-$unique"; // also HTML ID
		$div = "<div class='$class' id='$msgId'>";

		global $egHeaderFooterEnableAsyncHeader, $egHeaderFooterEnableAsyncFooter;

		$isHeader = $class === 'hf-nsheader' || $class === 'hf-header';
		$isFooter = $class === 'hf-nsfooter' || $class === 'hf-footer';
		$isCat = substr( $class, 0, 12) === 'hf-catheader' || substr( $class, 0, 12) === 'hf-catfooter';
		// Category headers or footers disable async loading!

		if ( !$isCat && ( ( $egHeaderFooterEnableAsyncFooter && $isFooter )
			|| ( $egHeaderFooterEnableAsyncHeader && $isHeader ) ) ) {

			// Just drop an empty div into the page. Will fill it with async
			// request after page load
			return $div . '</div>';
		}
		else {
			$msgText = wfMessage( $msgId )->parse();

			// don't need to bother if there is no content.
			if ( empty( $msgText ) ) {
				return null;
			}

			if ( wfMessage( $msgId )->inContentLanguage()->isBlank() ) {
				return null;
			}

			return $div . $msgText . '</div>';
		}
	}

	public static function onResourceLoaderGetConfigVars ( array &$vars ) {
		global $egHeaderFooterEnableAsyncHeader, $egHeaderFooterEnableAsyncFooter;

		$vars['egHeaderFooter'] = [
			'enableAsyncHeader' => $egHeaderFooterEnableAsyncHeader,
			'enableAsyncFooter' => $egHeaderFooterEnableAsyncFooter,
		];

		return true;
	}
}
