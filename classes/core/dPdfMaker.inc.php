<?php
/**
	As seguintes classes estão incorporadas neste arquivo:
	
	FPDF 1.7 (2011-06-18)
		https://github.com/hanneskod/fpdf
	
	HTML2PDF by Clément Lavoillotte (2009-06-14)
		http://www.fpdf.org/en/script/script42.php
	
	FPDI 1.5.4 (2015-05-18)
		https://github.com/Setasign/FPDI
		* Inclui também:
		  fpdf_tpl, fpdi_pdf_parser, pdf_context, pdf_parser, FilterASCII85, FilterASCIIHexDecode, FilterLZW.
	
	Localização das fontes:
		Constructor: settings['fontPath']
		Default: classes/dPdfMakerFonts/*
	
	Os seguintes utilitários deverão estar disponíveis:
		1: PDF Drawer     (admin/pdfmaker/pdf_drawer.php)
		2: Font Generator (admin/pdfmaker/font_generator.php)
		3: PDF Generator  (admin/pdfmaker/pdf_generator.php)
**/
class FPDF{
	var $page; // current page number
	var $n; // current object number
	var $offsets; // array of object offsets
	var $buffer; // buffer holding in-memory PDF
	var $pages; // array containing pages
	var $state; // current document state
	var $compress; // compression flag
	var $k; // scale factor (number of points in user unit)
	var $DefOrientation; // default orientation
	var $CurOrientation; // current orientation
	var $StdPageSizes; // standard page sizes
	var $DefPageSize; // default page size
	var $CurPageSize; // current page size
	var $PageSizes; // used for pages with non default sizes or orientations
	var $wPt, $hPt; // dimensions of current page in points
	var $w, $h;   // dimensions of current page in user unit
	var $lMargin; // left margin
	var $tMargin; // top margin
	var $rMargin; // right margin
	var $bMargin; // page break margin
	var $cMargin; // cell margin
	var $x, $y; // current position in user unit
	var $lasth; // height of last printed cell
	var $LineWidth; // line width in user unit
	var $fontpath; // path containing fonts
	var $CoreFonts; // array of core font names
	var $fonts; // array of used fonts
	var $FontFiles; // array of font files
	var $diffs; // array of encoding differences
	var $FontFamily; // current font family
	var $FontStyle; // current font style
	var $underline; // underlining flag
	var $CurrentFont; // current font info
	var $FontSizePt; // current font size in points
	var $FontSize; // current font size in user unit
	var $DrawColor; // commands for drawing color
	var $FillColor; // commands for filling color
	var $TextColor; // commands for text color
	var $ColorFlag; // indicates whether fill and text colors are different
	var $ws; // word spacing
	var $images; // array of used images
	var $PageLinks; // array of links in pages
	var $links; // array of internal links
	var $AutoPageBreak; // automatic page breaking
	var $PageBreakTrigger; // threshold used to trigger page breaks
	var $InHeader; // flag set when processing header
	var $InFooter; // flag set when processing footer
	var $ZoomMode; // zoom display mode
	var $LayoutMode; // layout display mode
	var $title; // title
	var $subject; // subject
	var $author; // author
	var $keywords; // keywords
	var $creator; // creator
	var $AliasNbPages; // alias for total number of pages
	var $PDFVersion; // PDF version number
	/*******************************************************************************
	 *                                                                              *
	 *                               Public methods                                 *
	 *                                                                              *
	 *******************************************************************************/
	function __construct($orientation = 'P', $unit = 'mm', $size = 'A4') {
		// Some checks
		$this->_dochecks();
		// Initialization of properties
		$this->page       = 0;
		$this->n          = 2;
		$this->buffer     = '';
		$this->pages      = array();
		$this->PageSizes  = array();
		$this->state      = 0;
		$this->fonts      = array();
		$this->FontFiles  = array();
		$this->diffs      = array();
		$this->images     = array();
		$this->links      = array();
		$this->InHeader   = false;
		$this->InFooter   = false;
		$this->lasth      = 0;
		$this->FontFamily = '';
		$this->FontStyle  = '';
		$this->FontSizePt = 12;
		$this->underline  = false;
		$this->DrawColor  = '0 G';
		$this->FillColor  = '0 g';
		$this->TextColor  = '0 g';
		$this->ColorFlag  = false;
		$this->ws         = 0;
		$this->fontpath   = '';
		
		// Core fonts
		$this->CoreFonts = array(
			'courier',
			'helvetica',
			'times',
			'symbol',
			'zapfdingbats'
		);
		// Scale factor
		if($unit == 'pt')
			$this->k = 1;
		elseif($unit == 'mm')
			$this->k = 72 / 25.4;
		elseif($unit == 'cm')
			$this->k = 72 / 2.54;
		elseif($unit == 'in')
			$this->k = 72;
		else
			$this->Error('Incorrect unit: ' . $unit);
		// Page sizes
		$this->StdPageSizes = array(
			'a3' => array(
				841.89,
				1190.55
			),
			'a4' => array(
				595.28,
				841.89
			),
			'a5' => array(
				420.94,
				595.28
			),
			'letter' => array(
				612,
				792
			),
			'legal' => array(
				612,
				1008
			)
		);
		$size               = $this->_getpagesize($size);
		$this->DefPageSize  = $size;
		$this->CurPageSize  = $size;
		// Page orientation
		$orientation        = strtolower($orientation);
		if($orientation == 'p' || $orientation == 'portrait') {
			$this->DefOrientation = 'P';
			$this->w              = $size[0];
			$this->h              = $size[1];
		} elseif($orientation == 'l' || $orientation == 'landscape') {
			$this->DefOrientation = 'L';
			$this->w              = $size[1];
			$this->h              = $size[0];
		} else
			$this->Error('Incorrect orientation: ' . $orientation);
		$this->CurOrientation = $this->DefOrientation;
		$this->wPt            = $this->w * $this->k;
		$this->hPt            = $this->h * $this->k;
		// Page margins (1 cm)
		$margin               = 28.35 / $this->k;
		$this->SetMargins($margin, $margin);
		// Interior cell margin (1 mm)
		$this->cMargin   = $margin / 10;
		// Line width (0.2 mm)
		$this->LineWidth = .567 / $this->k;
		// Automatic page break
		$this->SetAutoPageBreak(true, 2 * $margin);
		// Default display mode
		$this->SetDisplayMode('default');
		// Enable compression
		$this->SetCompression(true);
		// Set default PDF version number
		$this->PDFVersion = '1.3';
	}
	function SetMargins($left, $top, $right = null) {
		// Set left, top and right margins
		$this->lMargin = $left;
		$this->tMargin = $top;
		if($right === null)
			$right = $left;
		$this->rMargin = $right;
	}
	function SetLeftMargin($margin) {
		// Set left margin
		$this->lMargin = $margin;
		if($this->page > 0 && $this->x < $margin)
			$this->x = $margin;
	}
	function SetTopMargin($margin) {
		// Set top margin
		$this->tMargin = $margin;
	}
	function SetRightMargin($margin) {
		// Set right margin
		$this->rMargin = $margin;
	}
	function SetAutoPageBreak($auto, $margin = 0) {
		// Set auto page break mode and triggering margin
		$this->AutoPageBreak    = $auto;
		$this->bMargin          = $margin;
		$this->PageBreakTrigger = $this->h - $margin;
	}
	function SetDisplayMode($zoom, $layout = 'default') {
		// Set display mode in viewer
		if($zoom == 'fullpage' || $zoom == 'fullwidth' || $zoom == 'real' || $zoom == 'default' || !is_string($zoom))
			$this->ZoomMode = $zoom;
		else
			$this->Error('Incorrect zoom display mode: ' . $zoom);
		if($layout == 'single' || $layout == 'continuous' || $layout == 'two' || $layout == 'default')
			$this->LayoutMode = $layout;
		else
			$this->Error('Incorrect layout display mode: ' . $layout);
	}
	function SetCompression($compress) {
		// Set page compression
		if(function_exists('gzcompress'))
			$this->compress = $compress;
		else
			$this->compress = false;
	}
	function SetTitle($title, $isUTF8 = false) {
		// Title of document
		if($isUTF8)
			$title = $this->_UTF8toUTF16($title);
		$this->title = $title;
	}
	function SetSubject($subject, $isUTF8 = false) {
		// Subject of document
		if($isUTF8)
			$subject = $this->_UTF8toUTF16($subject);
		$this->subject = $subject;
	}
	function SetAuthor($author, $isUTF8 = false) {
		// Author of document
		if($isUTF8)
			$author = $this->_UTF8toUTF16($author);
		$this->author = $author;
	}
	function SetKeywords($keywords, $isUTF8 = false) {
		// Keywords of document
		if($isUTF8)
			$keywords = $this->_UTF8toUTF16($keywords);
		$this->keywords = $keywords;
	}
	function SetCreator($creator, $isUTF8 = false) {
		// Creator of document
		if($isUTF8)
			$creator = $this->_UTF8toUTF16($creator);
		$this->creator = $creator;
	}
	function AliasNbPages($alias = '{nb}') {
		// Define an alias for total number of pages
		$this->AliasNbPages = $alias;
	}
	function Error($msg) {
		// Fatal error
		//die('<b>FPDF error:</b> '.$msg);
		// Altered by itbz 2012-05-13
		throw new \RuntimeException($msg);
	}
	function Open() {
		// Begin document
		$this->state = 1;
	}
	function Close() {
		// Terminate document
		if($this->state == 3)
			return;
		if($this->page == 0)
			$this->AddPage();
		// Page footer
		$this->InFooter = true;
		$this->Footer();
		$this->InFooter = false;
		// Close page
		$this->_endpage();
		// Close document
		$this->_enddoc();
	}
	function AddPage($orientation = '', $size = '') {
		// Start a new page
		if($this->state == 0)
			$this->Open();
		$family   = $this->FontFamily;
		$style    = $this->FontStyle . ($this->underline ? 'U' : '');
		$fontsize = $this->FontSizePt;
		$lw       = $this->LineWidth;
		$dc       = $this->DrawColor;
		$fc       = $this->FillColor;
		$tc       = $this->TextColor;
		$cf       = $this->ColorFlag;
		if($this->page > 0) {
			// Page footer
			$this->InFooter = true;
			$this->Footer();
			$this->InFooter = false;
			// Close page
			$this->_endpage();
		}
		// Start new page
		$this->_beginpage($orientation, $size);
		// Set line cap style to square
		$this->_out('2 J');
		// Set line width
		$this->LineWidth = $lw;
		$this->_out(sprintf('%.2F w', $lw * $this->k));
		// Set font
		if($family)
			$this->SetFont($family, $style, $fontsize);
		// Set colors
		$this->DrawColor = $dc;
		if($dc != '0 G')
			$this->_out($dc);
		$this->FillColor = $fc;
		if($fc != '0 g')
			$this->_out($fc);
		$this->TextColor = $tc;
		$this->ColorFlag = $cf;
		// Page header
		$this->InHeader  = true;
		$this->Header();
		$this->InHeader = false;
		// Restore line width
		if($this->LineWidth != $lw) {
			$this->LineWidth = $lw;
			$this->_out(sprintf('%.2F w', $lw * $this->k));
		}
		// Restore font
		if($family)
			$this->SetFont($family, $style, $fontsize);
		// Restore colors
		if($this->DrawColor != $dc) {
			$this->DrawColor = $dc;
			$this->_out($dc);
		}
		if($this->FillColor != $fc) {
			$this->FillColor = $fc;
			$this->_out($fc);
		}
		$this->TextColor = $tc;
		$this->ColorFlag = $cf;
	}
	function Header() {
		// To be implemented in your own inherited class
	}
	function Footer() {
		// To be implemented in your own inherited class
	}
	function PageNo() {
		// Get current page number
		return $this->page;
	}
	function SetDrawColor($r, $g = null, $b = null) {
		// Set color for all stroking operations
		if(($r == 0 && $g == 0 && $b == 0) || $g === null)
			$this->DrawColor = sprintf('%.3F G', $r / 255);
		else
			$this->DrawColor = sprintf('%.3F %.3F %.3F RG', $r / 255, $g / 255, $b / 255);
		if($this->page > 0)
			$this->_out($this->DrawColor);
	}
	function SetFillColor($r, $g = null, $b = null) {
		// Set color for all filling operations
		if(($r == 0 && $g == 0 && $b == 0) || $g === null)
			$this->FillColor = sprintf('%.3F g', $r / 255);
		else
			$this->FillColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
		$this->ColorFlag = ($this->FillColor != $this->TextColor);
		if($this->page > 0)
			$this->_out($this->FillColor);
	}
	function SetTextColor($r, $g = null, $b = null) {
		// Set color for text
		if(($r == 0 && $g == 0 && $b == 0) || $g === null)
			$this->TextColor = sprintf('%.3F g', $r / 255);
		else
			$this->TextColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
		$this->ColorFlag = ($this->FillColor != $this->TextColor);
	}
	function GetStringWidth($s) {
		// Get width of a string in the current font
		$s = (string) $s;
		$cw =& $this->CurrentFont['cw'];
		$w = 0;
		$l = strlen($s);
		for($i = 0; $i < $l; $i++)
			$w += $cw[$s[$i]];
		return $w * $this->FontSize / 1000;
	}
	function SetLineWidth($width) {
		// Set line width
		$this->LineWidth = $width;
		if($this->page > 0)
			$this->_out(sprintf('%.2F w', $width * $this->k));
	}
	function Line($x1, $y1, $x2, $y2) {
		// Draw a line
		$this->_out(sprintf('%.2F %.2F m %.2F %.2F l S', $x1 * $this->k, ($this->h - $y1) * $this->k, $x2 * $this->k, ($this->h - $y2) * $this->k));
	}
	function Rect($x, $y, $w, $h, $style = '') {
		// Draw a rectangle
		if($style == 'F')
			$op = 'f';
		elseif($style == 'FD' || $style == 'DF')
			$op = 'B';
		else
			$op = 'S';
		$this->_out(sprintf('%.2F %.2F %.2F %.2F re %s', $x * $this->k, ($this->h - $y) * $this->k, $w * $this->k, -$h * $this->k, $op));
	}
	function AddFont($family, $style = '', $file = '') {
		// Add a TrueType, OpenType or Type1 font
		$family = strtolower($family);
		if($file == '')
			$file = str_replace(' ', '', $family) . strtolower($style) . '.php';
		$style = strtoupper($style);
		if($style == 'IB')
			$style = 'BI';
		$fontkey = $family . $style;
		if(isset($this->fonts[$fontkey]))
			return;
		$info      = $this->_loadfont($file);
		$info['i'] = count($this->fonts) + 1;
		if(!empty($info['diff'])) {
			// Search existing encodings
			$n = array_search($info['diff'], $this->diffs);
			if(!$n) {
				$n               = count($this->diffs) + 1;
				$this->diffs[$n] = $info['diff'];
			}
			$info['diffn'] = $n;
		}
		if(!empty($info['file'])) {
			// Embedded font
			if($info['type'] == 'TrueType')
				$this->FontFiles[$info['file']] = array(
					'length1' => $info['originalsize']
				);
			else
				$this->FontFiles[$info['file']] = array(
					'length1' => $info['size1'],
					'length2' => $info['size2']
				);
		}
		$this->fonts[$fontkey] = $info;
	}
	function SetFont($family, $style = '', $size = 0) {
		// Select a font; size given in points
		if($family == '')
			$family = $this->FontFamily;
		else
			$family = strtolower($family);
		$style = strtoupper($style);
		if(strpos($style, 'U') !== false) {
			$this->underline = true;
			$style           = str_replace('U', '', $style);
		} else
			$this->underline = false;
		if($style == 'IB')
			$style = 'BI';
		if($size == 0)
			$size = $this->FontSizePt;
		// Test if font is already selected
		if($this->FontFamily == $family && $this->FontStyle == $style && $this->FontSizePt == $size)
			return;
		// Test if font is already loaded
		$fontkey = $family . $style;
		if(!isset($this->fonts[$fontkey])) {
			// Test if one of the core fonts
			if($family == 'arial')
				$family = 'helvetica';
			if(in_array($family, $this->CoreFonts)) {
				if($family == 'symbol' || $family == 'zapfdingbats')
					$style = '';
				$fontkey = $family . $style;
				if(!isset($this->fonts[$fontkey]))
					$this->AddFont($family, $style);
			} else
				$this->Error('Undefined font: ' . $family . ' ' . $style);
		}
		// Select it
		$this->FontFamily = $family;
		$this->FontStyle  = $style;
		$this->FontSizePt = $size;
		$this->FontSize   = $size / $this->k;
		$this->CurrentFont =& $this->fonts[$fontkey];
		if($this->page > 0)
			$this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
	}
	function SetFontSize($size) {
		// Set font size in points
		if($this->FontSizePt == $size)
			return;
		$this->FontSizePt = $size;
		$this->FontSize   = $size / $this->k;
		if($this->page > 0)
			$this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
	}
	function AddLink() {
		// Create a new internal link
		$n               = count($this->links) + 1;
		$this->links[$n] = array(
			0,
			0
		);
		return $n;
	}
	function SetLink($link, $y = 0, $page = -1) {
		// Set destination of internal link
		if($y == -1)
			$y = $this->y;
		if($page == -1)
			$page = $this->page;
		$this->links[$link] = array(
			$page,
			$y
		);
	}
	function Link($x, $y, $w, $h, $link) {
		// Put a link on the page
		$this->PageLinks[$this->page][] = array(
			$x * $this->k,
			$this->hPt - $y * $this->k,
			$w * $this->k,
			$h * $this->k,
			$link
		);
	}
	function Text($x, $y, $txt) {
		// Output a string
		$s = sprintf('BT %.2F %.2F Td (%s) Tj ET', $x * $this->k, ($this->h - $y) * $this->k, $this->_escape($txt));
		if($this->underline && $txt != '')
			$s .= ' ' . $this->_dounderline($x, $y, $txt);
		if($this->ColorFlag)
			$s = 'q ' . $this->TextColor . ' ' . $s . ' Q';
		$this->_out($s);
	}
	function AcceptPageBreak() {
		// Accept automatic page break or not
		return $this->AutoPageBreak;
	}
	function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '') {
		// Output a cell
		$k = $this->k;
		if($this->y + $h > $this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak()) {
			// Automatic page break
			$x  = $this->x;
			$ws = $this->ws;
			if($ws > 0) {
				$this->ws = 0;
				$this->_out('0 Tw');
			}
			$this->AddPage($this->CurOrientation, $this->CurPageSize);
			$this->x = $x;
			if($ws > 0) {
				$this->ws = $ws;
				$this->_out(sprintf('%.3F Tw', $ws * $k));
			}
		}
		if($w == 0)
			$w = $this->w - $this->rMargin - $this->x;
		$s = '';
		if($fill || $border == 1) {
			if($fill)
				$op = ($border == 1) ? 'B' : 'f';
			else
				$op = 'S';
			$s = sprintf('%.2F %.2F %.2F %.2F re %s ', $this->x * $k, ($this->h - $this->y) * $k, $w * $k, -$h * $k, $op);
		}
		if(is_string($border)) {
			$x = $this->x;
			$y = $this->y;
			if(strpos($border, 'L') !== false)
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - $y) * $k, $x * $k, ($this->h - ($y + $h)) * $k);
			if(strpos($border, 'T') !== false)
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - $y) * $k, ($x + $w) * $k, ($this->h - $y) * $k);
			if(strpos($border, 'R') !== false)
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ', ($x + $w) * $k, ($this->h - $y) * $k, ($x + $w) * $k, ($this->h - ($y + $h)) * $k);
			if(strpos($border, 'B') !== false)
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - ($y + $h)) * $k, ($x + $w) * $k, ($this->h - ($y + $h)) * $k);
		}
		if($txt !== '') {
			if($align == 'R')
				$dx = $w - $this->cMargin - $this->GetStringWidth($txt);
			elseif($align == 'C')
				$dx = ($w - $this->GetStringWidth($txt)) / 2;
			else
				$dx = $this->cMargin;
			if($this->ColorFlag)
				$s .= 'q ' . $this->TextColor . ' ';
			$txt2 = str_replace(')', '\\)', str_replace('(', '\\(', str_replace('\\', '\\\\', $txt)));
			$s .= sprintf('BT %.2F %.2F Td (%s) Tj ET', ($this->x + $dx) * $k, ($this->h - ($this->y + .5 * $h + .3 * $this->FontSize)) * $k, $txt2);
			if($this->underline)
				$s .= ' ' . $this->_dounderline($this->x + $dx, $this->y + .5 * $h + .3 * $this->FontSize, $txt);
			if($this->ColorFlag)
				$s .= ' Q';
			if($link)
				$this->Link($this->x + $dx, $this->y + .5 * $h - .5 * $this->FontSize, $this->GetStringWidth($txt), $this->FontSize, $link);
		}
		if($s)
			$this->_out($s);
		$this->lasth = $h;
		if($ln > 0) {
			// Go to next line
			$this->y += $h;
			if($ln == 1)
				$this->x = $this->lMargin;
		} else
			$this->x += $w;
	}
	function MultiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = false) {
		// Output text with automatic or explicit line breaks
		$cw =& $this->CurrentFont['cw'];
		if($w == 0)
			$w = $this->w - $this->rMargin - $this->x;
		$wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
		$s    = str_replace("\r", '', $txt);
		$nb   = strlen($s);
		if($nb > 0 && $s[$nb - 1] == "\n")
			$nb--;
		$b = 0;
		if($border) {
			if($border == 1) {
				$border = 'LTRB';
				$b      = 'LRT';
				$b2     = 'LR';
			} else {
				$b2 = '';
				if(strpos($border, 'L') !== false)
					$b2 .= 'L';
				if(strpos($border, 'R') !== false)
					$b2 .= 'R';
				$b = (strpos($border, 'T') !== false) ? $b2 . 'T' : $b2;
			}
		}
		$sep = -1;
		$i   = 0;
		$j   = 0;
		$l   = 0;
		$ns  = 0;
		$nl  = 1;
		while($i < $nb) {
			// Get next character
			$c = $s[$i];
			if($c == "\n") {
				// Explicit line break
				if($this->ws > 0) {
					$this->ws = 0;
					$this->_out('0 Tw');
				}
				$this->Cell($w, $h, substr($s, $j, $i - $j), $b, 2, $align, $fill);
				$i++;
				$sep = -1;
				$j   = $i;
				$l   = 0;
				$ns  = 0;
				$nl++;
				if($border && $nl == 2)
					$b = $b2;
				continue;
			}
			if($c == ' ') {
				$sep = $i;
				$ls  = $l;
				$ns++;
			}
			$l += $cw[$c];
			if($l > $wmax) {
				// Automatic line break
				if($sep == -1) {
					if($i == $j)
						$i++;
					if($this->ws > 0) {
						$this->ws = 0;
						$this->_out('0 Tw');
					}
					$this->Cell($w, $h, substr($s, $j, $i - $j), $b, 2, $align, $fill);
				} else {
					if($align == 'J') {
						$this->ws = ($ns > 1) ? ($wmax - $ls) / 1000 * $this->FontSize / ($ns - 1) : 0;
						$this->_out(sprintf('%.3F Tw', $this->ws * $this->k));
					}
					$this->Cell($w, $h, substr($s, $j, $sep - $j), $b, 2, $align, $fill);
					$i = $sep + 1;
				}
				$sep = -1;
				$j   = $i;
				$l   = 0;
				$ns  = 0;
				$nl++;
				if($border && $nl == 2)
					$b = $b2;
			} else
				$i++;
		}
		// Last chunk
		if($this->ws > 0) {
			$this->ws = 0;
			$this->_out('0 Tw');
		}
		if($border && strpos($border, 'B') !== false)
			$b .= 'B';
		$this->Cell($w, $h, substr($s, $j, $i - $j), $b, 2, $align, $fill);
		$this->x = $this->lMargin;
	}
	function Write($h, $txt, $link = '') {
		// Output text in flowing mode
		$cw =& $this->CurrentFont['cw'];
		$w    = $this->w - $this->rMargin - $this->x;
		$wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
		$s    = str_replace("\r", '', $txt);
		$nb   = strlen($s);
		$sep  = -1;
		$i    = 0;
		$j    = 0;
		$l    = 0;
		$nl   = 1;
		while($i < $nb) {
			// Get next character
			$c = $s[$i];
			if($c == "\n") {
				// Explicit line break
				$this->Cell($w, $h, substr($s, $j, $i - $j), 0, 2, '', 0, $link);
				$i++;
				$sep = -1;
				$j   = $i;
				$l   = 0;
				if($nl == 1) {
					$this->x = $this->lMargin;
					$w       = $this->w - $this->rMargin - $this->x;
					$wmax    = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
				}
				$nl++;
				continue;
			}
			if($c == ' ')
				$sep = $i;
			$l += $cw[$c];
			if($l > $wmax) {
				// Automatic line break
				if($sep == -1) {
					if($this->x > $this->lMargin) {
						// Move to next line
						$this->x = $this->lMargin;
						$this->y += $h;
						$w    = $this->w - $this->rMargin - $this->x;
						$wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
						$i++;
						$nl++;
						continue;
					}
					if($i == $j)
						$i++;
					$this->Cell($w, $h, substr($s, $j, $i - $j), 0, 2, '', 0, $link);
				} else {
					$this->Cell($w, $h, substr($s, $j, $sep - $j), 0, 2, '', 0, $link);
					$i = $sep + 1;
				}
				$sep = -1;
				$j   = $i;
				$l   = 0;
				if($nl == 1) {
					$this->x = $this->lMargin;
					$w       = $this->w - $this->rMargin - $this->x;
					$wmax    = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
				}
				$nl++;
			} else
				$i++;
		}
		// Last chunk
		if($i != $j)
			$this->Cell($l / 1000 * $this->FontSize, $h, substr($s, $j), 0, 0, '', 0, $link);
	}
	function Ln($h = null) {
		// Line feed; default value is last cell height
		$this->x = $this->lMargin;
		if($h === null)
			$this->y += $this->lasth;
		else
			$this->y += $h;
	}
	function Image($file, $x = null, $y = null, $w = 0, $h = 0, $type = '', $link = '') {
		// Put an image on the page
		if(!isset($this->images[$file])) {
			// First use of this image, get info
			if($type == '') {
				$pos = strrpos($file, '.');
				if(!$pos)
					$this->Error('Image file has no extension and no type was specified: ' . $file);
				$type = substr($file, $pos + 1);
			}
			$type = strtolower($type);
			if($type == 'jpeg')
				$type = 'jpg';
			$mtd = '_parse' . $type;
			if(!method_exists($this, $mtd))
				$this->Error('Unsupported image type: ' . $type);
			$info                = $this->$mtd($file);
			$info['i']           = count($this->images) + 1;
			$this->images[$file] = $info;
		} else
			$info = $this->images[$file];
		// Automatic width and height calculation if needed
		if($w == 0 && $h == 0) {
			// Put image at 96 dpi
			$w = -96;
			$h = -96;
		}
		if($w < 0)
			$w = -$info['w'] * 72 / $w / $this->k;
		if($h < 0)
			$h = -$info['h'] * 72 / $h / $this->k;
		if($w == 0)
			$w = $h * $info['w'] / $info['h'];
		if($h == 0)
			$h = $w * $info['h'] / $info['w'];
		// Flowing mode
		if($y === null) {
			if($this->y + $h > $this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak()) {
				// Automatic page break
				$x2 = $this->x;
				$this->AddPage($this->CurOrientation, $this->CurPageSize);
				$this->x = $x2;
			}
			$y = $this->y;
			$this->y += $h;
		}
		if($x === null)
			$x = $this->x;
		$this->_out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /I%d Do Q', $w * $this->k, $h * $this->k, $x * $this->k, ($this->h - ($y + $h)) * $this->k, $info['i']));
		if($link)
			$this->Link($x, $y, $w, $h, $link);
	}
	function GetX() {
		// Get x position
		return $this->x;
	}
	function SetX($x) {
		// Set x position
		if($x >= 0)
			$this->x = $x;
		else
			$this->x = $this->w + $x;
	}
	function GetY() {
		// Get y position
		return $this->y;
	}
	function SetY($y) {
		// Set y position and reset x
		$this->x = $this->lMargin;
		if($y >= 0)
			$this->y = $y;
		else
			$this->y = $this->h + $y;
	}
	function SetXY($x, $y) {
		// Set x and y positions
		$this->SetY($y);
		$this->SetX($x);
	}
	function Output($name = '', $dest = '') {
		// Output PDF to some destination
		if($this->state < 3)
			$this->Close();
		$dest = strtoupper($dest);
		if($dest == '') {
			if($name == '') {
				$name = 'doc.pdf';
				$dest = 'I';
			} else
				$dest = 'F';
		}
		switch($dest) {
			case 'I':
				// Send to standard output
				$this->_checkoutput();
				if(PHP_SAPI != 'cli') {
					// We send to a browser
					header('Content-Type: application/pdf');
					header('Content-Disposition: inline; filename="' . $name . '"');
					header('Cache-Control: private, max-age=0, must-revalidate');
					header('Pragma: public');
				}
				echo $this->buffer;
				break;
			case 'D':
				// Download file
				$this->_checkoutput();
				header('Content-Type: application/x-download');
				header('Content-Disposition: attachment; filename="' . $name . '"');
				header('Cache-Control: private, max-age=0, must-revalidate');
				header('Pragma: public');
				echo $this->buffer;
				break;
			case 'F':
				// Save to local file
				$f = fopen($name, 'wb');
				if(!$f)
					$this->Error('Unable to create output file: ' . $name);
				fwrite($f, $this->buffer, strlen($this->buffer));
				fclose($f);
				break;
			case 'S':
				// Return as a string
				return $this->buffer;
			default:
				$this->Error('Incorrect output destination: ' . $dest);
		}
		return '';
	}
	
	/*******************************************************************************
	 *                                                                              *
	 *                              Protected methods                               *
	 *                                                                              *
	 *******************************************************************************/
	function _dochecks() {
		// Check availability of %F
		if(sprintf('%.1F', 1.0) != '1.0')
			$this->Error('This version of PHP is not supported');
		// Check mbstring overloading
		if(ini_get('mbstring.func_overload') & 2)
			$this->Error('mbstring overloading must be disabled');
		// Ensure runtime magic quotes are disabled
		if(get_magic_quotes_runtime())
			@set_magic_quotes_runtime(0);
	}
	function _checkoutput() {
		if(PHP_SAPI != 'cli') {
			if(headers_sent($file, $line))
				$this->Error("Some data has already been output, can't send PDF file (output started at $file:$line)");
		}
		if(ob_get_length()) {
			// The output buffer is not empty
			if(preg_match('/^(\xEF\xBB\xBF)?\s*$/', ob_get_contents())) {
				// It contains only a UTF-8 BOM and/or whitespace, let's clean it
				ob_clean();
			} else
				$this->Error("Some data has already been output, can't send PDF file");
		}
	}
	function _getpagesize($size) {
		if(is_string($size)) {
			$size = strtolower($size);
			if(!isset($this->StdPageSizes[$size]))
				$this->Error('Unknown page size: ' . $size);
			$a = $this->StdPageSizes[$size];
			return array(
				$a[0] / $this->k,
				$a[1] / $this->k
			);
		} else {
			if($size[0] > $size[1])
				return array(
					$size[1],
					$size[0]
				);
			else
				return $size;
		}
	}
	function _beginpage($orientation, $size) {
		$this->page++;
		$this->pages[$this->page] = '';
		$this->state              = 2;
		$this->x                  = $this->lMargin;
		$this->y                  = $this->tMargin;
		$this->FontFamily         = '';
		// Check page size and orientation
		if($orientation == '')
			$orientation = $this->DefOrientation;
		else
			$orientation = strtoupper($orientation[0]);
		if($size == '')
			$size = $this->DefPageSize;
		else
			$size = $this->_getpagesize($size);
		if($orientation != $this->CurOrientation || $size[0] != $this->CurPageSize[0] || $size[1] != $this->CurPageSize[1]) {
			// New size or orientation
			if($orientation == 'P') {
				$this->w = $size[0];
				$this->h = $size[1];
			} else {
				$this->w = $size[1];
				$this->h = $size[0];
			}
			$this->wPt              = $this->w * $this->k;
			$this->hPt              = $this->h * $this->k;
			$this->PageBreakTrigger = $this->h - $this->bMargin;
			$this->CurOrientation   = $orientation;
			$this->CurPageSize      = $size;
		}
		if($orientation != $this->DefOrientation || $size[0] != $this->DefPageSize[0] || $size[1] != $this->DefPageSize[1])
			$this->PageSizes[$this->page] = array(
				$this->wPt,
				$this->hPt
			);
	}
	function _endpage() {
		$this->state = 1;
	}
	function _loadfont($font) {
		// Load a font definition file from the font directory
		include($this->fontpath . $font);
		$a = get_defined_vars();
		if(!isset($a['name']))
			$this->Error('Could not include font definition file');
		return $a;
	}
	function _escape($s) {
		// Escape special characters in strings
		$s = str_replace('\\', '\\\\', $s);
		$s = str_replace('(', '\\(', $s);
		$s = str_replace(')', '\\)', $s);
		$s = str_replace("\r", '\\r', $s);
		return $s;
	}
	function _textstring($s) {
		// Format a text string
		return '(' . $this->_escape($s) . ')';
	}
	function _UTF8toUTF16($s) {
		// Convert UTF-8 to UTF-16BE with BOM
		$res = "\xFE\xFF";
		$nb  = strlen($s);
		$i   = 0;
		while($i < $nb) {
			$c1 = ord($s[$i++]);
			if($c1 >= 224) {
				// 3-byte character
				$c2 = ord($s[$i++]);
				$c3 = ord($s[$i++]);
				$res .= chr((($c1 & 0x0F) << 4) + (($c2 & 0x3C) >> 2));
				$res .= chr((($c2 & 0x03) << 6) + ($c3 & 0x3F));
			} elseif($c1 >= 192) {
				// 2-byte character
				$c2 = ord($s[$i++]);
				$res .= chr(($c1 & 0x1C) >> 2);
				$res .= chr((($c1 & 0x03) << 6) + ($c2 & 0x3F));
			} else {
				// Single-byte character
				$res .= "\0" . chr($c1);
			}
		}
		return $res;
	}
	function _dounderline($x, $y, $txt) {
		// Underline text
		$up = $this->CurrentFont['up'];
		$ut = $this->CurrentFont['ut'];
		$w  = $this->GetStringWidth($txt) + $this->ws * substr_count($txt, ' ');
		return sprintf('%.2F %.2F %.2F %.2F re f', $x * $this->k, ($this->h - ($y - $up / 1000 * $this->FontSize)) * $this->k, $w * $this->k, -$ut / 1000 * $this->FontSizePt);
	}
	function _parsejpg($file) {
		// Extract info from a JPEG file
		$a = getimagesize($file);
		if(!$a)
			$this->Error('Missing or incorrect image file: ' . $file);
		if($a[2] != 2)
			$this->Error('Not a JPEG file: ' . $file);
		if(!isset($a['channels']) || $a['channels'] == 3)
			$colspace = 'DeviceRGB';
		elseif($a['channels'] == 4)
			$colspace = 'DeviceCMYK';
		else
			$colspace = 'DeviceGray';
		$bpc  = isset($a['bits']) ? $a['bits'] : 8;
		$data = file_get_contents($file);
		return array(
			'w' => $a[0],
			'h' => $a[1],
			'cs' => $colspace,
			'bpc' => $bpc,
			'f' => 'DCTDecode',
			'data' => $data
		);
	}
	function _parsepng($file) {
		// Extract info from a PNG file
		$f = fopen($file, 'rb');
		if(!$f)
			$this->Error('Can\'t open image file: ' . $file);
		$info = $this->_parsepngstream($f, $file);
		fclose($f);
		return $info;
	}
	function _parsepngstream($f, $file) {
		// Check signature
		if($this->_readstream($f, 8) != chr(137) . 'PNG' . chr(13) . chr(10) . chr(26) . chr(10))
			$this->Error('Not a PNG file: ' . $file);
		// Read header chunk
		$this->_readstream($f, 4);
		if($this->_readstream($f, 4) != 'IHDR')
			$this->Error('Incorrect PNG file: ' . $file);
		$w   = $this->_readint($f);
		$h   = $this->_readint($f);
		$bpc = ord($this->_readstream($f, 1));
		if($bpc > 8)
			$this->Error('16-bit depth not supported: ' . $file);
		$ct = ord($this->_readstream($f, 1));
		if($ct == 0 || $ct == 4)
			$colspace = 'DeviceGray';
		elseif($ct == 2 || $ct == 6)
			$colspace = 'DeviceRGB';
		elseif($ct == 3)
			$colspace = 'Indexed';
		else
			$this->Error('Unknown color type: ' . $file);
		if(ord($this->_readstream($f, 1)) != 0)
			$this->Error('Unknown compression method: ' . $file);
		if(ord($this->_readstream($f, 1)) != 0)
			$this->Error('Unknown filter method: ' . $file);
		if(ord($this->_readstream($f, 1)) != 0)
			$this->Error('Interlacing not supported: ' . $file);
		$this->_readstream($f, 4);
		$dp   = '/Predictor 15 /Colors ' . ($colspace == 'DeviceRGB' ? 3 : 1) . ' /BitsPerComponent ' . $bpc . ' /Columns ' . $w;
		// Scan chunks looking for palette, transparency and image data
		$pal  = '';
		$trns = '';
		$data = '';
		do {
			$n    = $this->_readint($f);
			$type = $this->_readstream($f, 4);
			if($type == 'PLTE') {
				// Read palette
				$pal = $this->_readstream($f, $n);
				$this->_readstream($f, 4);
			} elseif($type == 'tRNS') {
				// Read transparency info
				$t = $this->_readstream($f, $n);
				if($ct == 0)
					$trns = array(
						ord(substr($t, 1, 1))
					);
				elseif($ct == 2)
					$trns = array(
						ord(substr($t, 1, 1)),
						ord(substr($t, 3, 1)),
						ord(substr($t, 5, 1))
					);
				else {
					$pos = strpos($t, chr(0));
					if($pos !== false)
						$trns = array(
							$pos
						);
				}
				$this->_readstream($f, 4);
			} elseif($type == 'IDAT') {
				// Read image data block
				$data .= $this->_readstream($f, $n);
				$this->_readstream($f, 4);
			} elseif($type == 'IEND')
				break;
			else
				$this->_readstream($f, $n + 4);
		} while($n);
		if($colspace == 'Indexed' && empty($pal))
			$this->Error('Missing palette in ' . $file);
		$info = array(
			'w' => $w,
			'h' => $h,
			'cs' => $colspace,
			'bpc' => $bpc,
			'f' => 'FlateDecode',
			'dp' => $dp,
			'pal' => $pal,
			'trns' => $trns
		);
		if($ct >= 4) {
			// Extract alpha channel
			if(!function_exists('gzuncompress'))
				$this->Error('Zlib not available, can\'t handle alpha channel: ' . $file);
			$data  = gzuncompress($data);
			$color = '';
			$alpha = '';
			if($ct == 4) {
				// Gray image
				$len = 2 * $w;
				for($i = 0; $i < $h; $i++) {
					$pos = (1 + $len) * $i;
					$color .= $data[$pos];
					$alpha .= $data[$pos];
					$line = substr($data, $pos + 1, $len);
					$color .= preg_replace('/(.)./s', '$1', $line);
					$alpha .= preg_replace('/.(.)/s', '$1', $line);
				}
			} else {
				// RGB image
				$len = 4 * $w;
				for($i = 0; $i < $h; $i++) {
					$pos = (1 + $len) * $i;
					$color .= $data[$pos];
					$alpha .= $data[$pos];
					$line = substr($data, $pos + 1, $len);
					$color .= preg_replace('/(.{3})./s', '$1', $line);
					$alpha .= preg_replace('/.{3}(.)/s', '$1', $line);
				}
			}
			unset($data);
			$data          = gzcompress($color);
			$info['smask'] = gzcompress($alpha);
			if($this->PDFVersion < '1.4')
				$this->PDFVersion = '1.4';
		}
		$info['data'] = $data;
		return $info;
	}
	function _readstream($f, $n) {
		// Read n bytes from stream
		$res = '';
		while($n > 0 && !feof($f)) {
			$s = fread($f, $n);
			if($s === false)
				$this->Error('Error while reading stream');
			$n -= strlen($s);
			$res .= $s;
		}
		if($n > 0)
			$this->Error('Unexpected end of stream');
		return $res;
	}
	function _readint($f) {
		// Read a 4-byte integer from stream
		$a = unpack('Ni', $this->_readstream($f, 4));
		return $a['i'];
	}
	function _parsegif($file) {
		// Extract info from a GIF file (via PNG conversion)
		if(!function_exists('imagepng'))
			$this->Error('GD extension is required for GIF support');
		if(!function_exists('imagecreatefromgif'))
			$this->Error('GD has no GIF read support');
		$im = imagecreatefromgif($file);
		if(!$im)
			$this->Error('Missing or incorrect image file: ' . $file);
		imageinterlace($im, 0);
		$f = @fopen('php://temp', 'rb+');
		if($f) {
			// Perform conversion in memory
			ob_start();
			imagepng($im);
			$data = ob_get_clean();
			imagedestroy($im);
			fwrite($f, $data);
			rewind($f);
			$info = $this->_parsepngstream($f, $file);
			fclose($f);
		} else {
			// Use temporary file
			$tmp = tempnam('.', 'gif');
			if(!$tmp)
				$this->Error('Unable to create a temporary file');
			if(!imagepng($im, $tmp))
				$this->Error('Error while saving to temporary file');
			imagedestroy($im);
			$info = $this->_parsepng($tmp);
			unlink($tmp);
		}
		return $info;
	}
	function _newobj() {
		// Begin a new object
		$this->n++;
		$this->offsets[$this->n] = strlen($this->buffer);
		$this->_out($this->n . ' 0 obj');
	}
	function _putstream($s) {
		$this->_out('stream');
		$this->_out($s);
		$this->_out('endstream');
	}
	function _out($s) {
		// Add a line to the document
		if($this->state == 2)
			$this->pages[$this->page] .= $s . "\n";
		else
			$this->buffer .= $s . "\n";
	}
	function _putpages() {
		$nb = $this->page;
		if(!empty($this->AliasNbPages)) {
			// Replace number of pages
			for($n = 1; $n <= $nb; $n++)
				$this->pages[$n] = str_replace($this->AliasNbPages, $nb, $this->pages[$n]);
		}
		if($this->DefOrientation == 'P') {
			$wPt = $this->DefPageSize[0] * $this->k;
			$hPt = $this->DefPageSize[1] * $this->k;
		} else {
			$wPt = $this->DefPageSize[1] * $this->k;
			$hPt = $this->DefPageSize[0] * $this->k;
		}
		$filter = ($this->compress) ? '/Filter /FlateDecode ' : '';
		for($n = 1; $n <= $nb; $n++) {
			// Page
			$this->_newobj();
			$this->_out('<</Type /Page');
			$this->_out('/Parent 1 0 R');
			if(isset($this->PageSizes[$n]))
				$this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]', $this->PageSizes[$n][0], $this->PageSizes[$n][1]));
			$this->_out('/Resources 2 0 R');
			if(isset($this->PageLinks[$n])) {
				// Links
				$annots = '/Annots [';
				foreach($this->PageLinks[$n] as $pl) {
					$rect = sprintf('%.2F %.2F %.2F %.2F', $pl[0], $pl[1], $pl[0] + $pl[2], $pl[1] - $pl[3]);
					$annots .= '<</Type /Annot /Subtype /Link /Rect [' . $rect . '] /Border [0 0 0] ';
					if(is_string($pl[4]))
						$annots .= '/A <</S /URI /URI ' . $this->_textstring($pl[4]) . '>>>>';
					else {
						$l = $this->links[$pl[4]];
						$h = isset($this->PageSizes[$l[0]]) ? $this->PageSizes[$l[0]][1] : $hPt;
						$annots .= sprintf('/Dest [%d 0 R /XYZ 0 %.2F null]>>', 1 + 2 * $l[0], $h - $l[1] * $this->k);
					}
				}
				$this->_out($annots . ']');
			}
			if($this->PDFVersion > '1.3')
				$this->_out('/Group <</Type /Group /S /Transparency /CS /DeviceRGB>>');
			$this->_out('/Contents ' . ($this->n + 1) . ' 0 R>>');
			$this->_out('endobj');
			// Page content
			$p = ($this->compress) ? gzcompress($this->pages[$n]) : $this->pages[$n];
			$this->_newobj();
			$this->_out('<<' . $filter . '/Length ' . strlen($p) . '>>');
			$this->_putstream($p);
			$this->_out('endobj');
		}
		// Pages root
		$this->offsets[1] = strlen($this->buffer);
		$this->_out('1 0 obj');
		$this->_out('<</Type /Pages');
		$kids = '/Kids [';
		for($i = 0; $i < $nb; $i++)
			$kids .= (3 + 2 * $i) . ' 0 R ';
		$this->_out($kids . ']');
		$this->_out('/Count ' . $nb);
		$this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]', $wPt, $hPt));
		$this->_out('>>');
		$this->_out('endobj');
	}
	function _putfonts() {
		$nf = $this->n;
		foreach($this->diffs as $diff) {
			// Encodings
			$this->_newobj();
			$this->_out('<</Type /Encoding /BaseEncoding /WinAnsiEncoding /Differences [' . $diff . ']>>');
			$this->_out('endobj');
		}
		foreach($this->FontFiles as $file => $info) {
			// Font file embedding
			$this->_newobj();
			$this->FontFiles[$file]['n'] = $this->n;
			$font                        = file_get_contents($this->fontpath . $file, true);
			if(!$font)
				$this->Error('Font file not found: ' . $file);
			$compressed = (substr($file, -2) == '.z');
			if(!$compressed && isset($info['length2']))
				$font = substr($font, 6, $info['length1']) . substr($font, 6 + $info['length1'] + 6, $info['length2']);
			$this->_out('<</Length ' . strlen($font));
			if($compressed)
				$this->_out('/Filter /FlateDecode');
			$this->_out('/Length1 ' . $info['length1']);
			if(isset($info['length2']))
				$this->_out('/Length2 ' . $info['length2'] . ' /Length3 0');
			$this->_out('>>');
			$this->_putstream($font);
			$this->_out('endobj');
		}
		foreach($this->fonts as $k => $font) {
			// Font objects
			$this->fonts[$k]['n'] = $this->n + 1;
			$type                 = $font['type'];
			$name                 = $font['name'];
			if($type == 'Core') {
				// Core font
				$this->_newobj();
				$this->_out('<</Type /Font');
				$this->_out('/BaseFont /' . $name);
				$this->_out('/Subtype /Type1');
				if($name != 'Symbol' && $name != 'ZapfDingbats')
					$this->_out('/Encoding /WinAnsiEncoding');
				$this->_out('>>');
				$this->_out('endobj');
			} elseif($type == 'Type1' || $type == 'TrueType') {
				// Additional Type1 or TrueType/OpenType font
				$this->_newobj();
				$this->_out('<</Type /Font');
				$this->_out('/BaseFont /' . $name);
				$this->_out('/Subtype /' . $type);
				$this->_out('/FirstChar 32 /LastChar 255');
				$this->_out('/Widths ' . ($this->n + 1) . ' 0 R');
				$this->_out('/FontDescriptor ' . ($this->n + 2) . ' 0 R');
				if(isset($font['diffn']))
					$this->_out('/Encoding ' . ($nf + $font['diffn']) . ' 0 R');
				else
					$this->_out('/Encoding /WinAnsiEncoding');
				$this->_out('>>');
				$this->_out('endobj');
				// Widths
				$this->_newobj();
				$cw =& $font['cw'];
				$s = '[';
				for($i = 32; $i <= 255; $i++)
					$s .= $cw[chr($i)] . ' ';
				$this->_out($s . ']');
				$this->_out('endobj');
				// Descriptor
				$this->_newobj();
				$s = '<</Type /FontDescriptor /FontName /' . $name;
				foreach($font['desc'] as $k => $v)
					$s .= ' /' . $k . ' ' . $v;
				if(!empty($font['file']))
					$s .= ' /FontFile' . ($type == 'Type1' ? '' : '2') . ' ' . $this->FontFiles[$font['file']]['n'] . ' 0 R';
				$this->_out($s . '>>');
				$this->_out('endobj');
			} else {
				// Allow for additional types
				$mtd = '_put' . strtolower($type);
				if(!method_exists($this, $mtd))
					$this->Error('Unsupported font type: ' . $type);
				$this->$mtd($font);
			}
		}
	}
	function _putimages() {
		foreach(array_keys($this->images) as $file) {
			$this->_putimage($this->images[$file]);
			unset($this->images[$file]['data']);
			unset($this->images[$file]['smask']);
		}
	}
	function _putimage(&$info) {
		$this->_newobj();
		$info['n'] = $this->n;
		$this->_out('<</Type /XObject');
		$this->_out('/Subtype /Image');
		$this->_out('/Width ' . $info['w']);
		$this->_out('/Height ' . $info['h']);
		if($info['cs'] == 'Indexed')
			$this->_out('/ColorSpace [/Indexed /DeviceRGB ' . (strlen($info['pal']) / 3 - 1) . ' ' . ($this->n + 1) . ' 0 R]');
		else {
			$this->_out('/ColorSpace /' . $info['cs']);
			if($info['cs'] == 'DeviceCMYK')
				$this->_out('/Decode [1 0 1 0 1 0 1 0]');
		}
		$this->_out('/BitsPerComponent ' . $info['bpc']);
		if(isset($info['f']))
			$this->_out('/Filter /' . $info['f']);
		if(isset($info['dp']))
			$this->_out('/DecodeParms <<' . $info['dp'] . '>>');
		if(isset($info['trns']) && is_array($info['trns'])) {
			$trns = '';
			for($i = 0; $i < count($info['trns']); $i++)
				$trns .= $info['trns'][$i] . ' ' . $info['trns'][$i] . ' ';
			$this->_out('/Mask [' . $trns . ']');
		}
		if(isset($info['smask']))
			$this->_out('/SMask ' . ($this->n + 1) . ' 0 R');
		$this->_out('/Length ' . strlen($info['data']) . '>>');
		$this->_putstream($info['data']);
		$this->_out('endobj');
		// Soft mask
		if(isset($info['smask'])) {
			$dp    = '/Predictor 15 /Colors 1 /BitsPerComponent 8 /Columns ' . $info['w'];
			$smask = array(
				'w' => $info['w'],
				'h' => $info['h'],
				'cs' => 'DeviceGray',
				'bpc' => 8,
				'f' => $info['f'],
				'dp' => $dp,
				'data' => $info['smask']
			);
			$this->_putimage($smask);
		}
		// Palette
		if($info['cs'] == 'Indexed') {
			$filter = ($this->compress) ? '/Filter /FlateDecode ' : '';
			$pal    = ($this->compress) ? gzcompress($info['pal']) : $info['pal'];
			$this->_newobj();
			$this->_out('<<' . $filter . '/Length ' . strlen($pal) . '>>');
			$this->_putstream($pal);
			$this->_out('endobj');
		}
	}
	function _putxobjectdict() {
		foreach($this->images as $image)
			$this->_out('/I' . $image['i'] . ' ' . $image['n'] . ' 0 R');
	}
	function _putresourcedict() {
		$this->_out('/ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
		$this->_out('/Font <<');
		foreach($this->fonts as $font)
			$this->_out('/F' . $font['i'] . ' ' . $font['n'] . ' 0 R');
		$this->_out('>>');
		$this->_out('/XObject <<');
		$this->_putxobjectdict();
		$this->_out('>>');
	}
	function _putresources() {
		$this->_putfonts();
		$this->_putimages();
		// Resource dictionary
		$this->offsets[2] = strlen($this->buffer);
		$this->_out('2 0 obj');
		$this->_out('<<');
		$this->_putresourcedict();
		$this->_out('>>');
		$this->_out('endobj');
	}
	function _putinfo() {
		$this->_out('/Producer ' . $this->_textstring('IMAGINACOM'));
		if(!empty($this->title))
			$this->_out('/Title ' . $this->_textstring($this->title));
		if(!empty($this->subject))
			$this->_out('/Subject ' . $this->_textstring($this->subject));
		if(!empty($this->author))
			$this->_out('/Author ' . $this->_textstring($this->author));
		if(!empty($this->keywords))
			$this->_out('/Keywords ' . $this->_textstring($this->keywords));
		if(!empty($this->creator))
			$this->_out('/Creator ' . $this->_textstring($this->creator));
		$this->_out('/CreationDate ' . $this->_textstring('D:' . @date('YmdHis')));
	}
	function _putcatalog() {
		$this->_out('/Type /Catalog');
		$this->_out('/Pages 1 0 R');
		if($this->ZoomMode == 'fullpage')
			$this->_out('/OpenAction [3 0 R /Fit]');
		elseif($this->ZoomMode == 'fullwidth')
			$this->_out('/OpenAction [3 0 R /FitH null]');
		elseif($this->ZoomMode == 'real')
			$this->_out('/OpenAction [3 0 R /XYZ null null 1]');
		elseif(!is_string($this->ZoomMode))
			$this->_out('/OpenAction [3 0 R /XYZ null null ' . sprintf('%.2F', $this->ZoomMode / 100) . ']');
		if($this->LayoutMode == 'single')
			$this->_out('/PageLayout /SinglePage');
		elseif($this->LayoutMode == 'continuous')
			$this->_out('/PageLayout /OneColumn');
		elseif($this->LayoutMode == 'two')
			$this->_out('/PageLayout /TwoColumnLeft');
	}
	function _putheader() {
		$this->_out('%PDF-' . $this->PDFVersion);
	}
	function _puttrailer() {
		$this->_out('/Size ' . ($this->n + 1));
		$this->_out('/Root ' . $this->n . ' 0 R');
		$this->_out('/Info ' . ($this->n - 1) . ' 0 R');
	}
	function _enddoc() {
		$this->_putheader();
		$this->_putpages();
		$this->_putresources();
		// Info
		$this->_newobj();
		$this->_out('<<');
		$this->_putinfo();
		$this->_out('>>');
		$this->_out('endobj');
		// Catalog
		$this->_newobj();
		$this->_out('<<');
		$this->_putcatalog();
		$this->_out('>>');
		$this->_out('endobj');
		// Cross-ref
		$o = strlen($this->buffer);
		$this->_out('xref');
		$this->_out('0 ' . ($this->n + 1));
		$this->_out('0000000000 65535 f ');
		for($i = 1; $i <= $this->n; $i++)
			$this->_out(sprintf('%010d 00000 n ', $this->offsets[$i]));
		// Trailer
		$this->_out('trailer');
		$this->_out('<<');
		$this->_puttrailer();
		$this->_out('>>');
		$this->_out('startxref');
		$this->_out($o);
		$this->_out('%%EOF');
		$this->state = 3;
	}
}
class FPDF_TPL        extends FPDF     {
	protected $_tpls = array();
	public $tpl = 0;
	protected $_inTpl = false;
	public $tplPrefix = "/TPL";
	protected $_res = array();
	public $lastUsedTemplateData = array();
	public function beginTemplate($x = null, $y = null, $w = null, $h = null) {
		if(is_subclass_of($this, 'TCPDF')) {
			throw new LogicException('This method is only usable with FPDF. Use TCPDF methods startTemplate() instead.');
		}
		if($this->page <= 0) {
			throw new LogicException("You have to add at least a page first!");
		}
		if($x == null)
			$x = 0;
		if($y == null)
			$y = 0;
		if($w == null)
			$w = $this->w;
		if($h == null)
			$h = $this->h;
		$this->tpl++;
		$tpl =& $this->_tpls[$this->tpl];
		$tpl = array(
			'o_x' => $this->x,
			'o_y' => $this->y,
			'o_AutoPageBreak' => $this->AutoPageBreak,
			'o_bMargin' => $this->bMargin,
			'o_tMargin' => $this->tMargin,
			'o_lMargin' => $this->lMargin,
			'o_rMargin' => $this->rMargin,
			'o_h' => $this->h,
			'o_w' => $this->w,
			'o_FontFamily' => $this->FontFamily,
			'o_FontStyle' => $this->FontStyle,
			'o_FontSizePt' => $this->FontSizePt,
			'o_FontSize' => $this->FontSize,
			'buffer' => '',
			'x' => $x,
			'y' => $y,
			'w' => $w,
			'h' => $h
		);
		$this->SetAutoPageBreak(false);
		$this->h      = $h;
		$this->w      = $w;
		$this->_inTpl = true;
		$this->SetXY($x + $this->lMargin, $y + $this->tMargin);
		$this->SetRightMargin($this->w - $w + $this->rMargin);
		if($this->CurrentFont) {
			$fontKey = $this->FontFamily . $this->FontStyle;
			if($fontKey) {
				$this->_res['tpl'][$this->tpl]['fonts'][$fontKey] =& $this->fonts[$fontKey];
				$this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
			}
		}
		return $this->tpl;
	}
	public function endTemplate() {
		if(is_subclass_of($this, 'TCPDF')) {
			$args = func_get_args();
			return call_user_func_array(array(
				$this,
				'TCPDF::endTemplate'
			), $args);
		}
		if($this->_inTpl) {
			$this->_inTpl = false;
			$tpl          = $this->_tpls[$this->tpl];
			$this->SetXY($tpl['o_x'], $tpl['o_y']);
			$this->tMargin = $tpl['o_tMargin'];
			$this->lMargin = $tpl['o_lMargin'];
			$this->rMargin = $tpl['o_rMargin'];
			$this->h       = $tpl['o_h'];
			$this->w       = $tpl['o_w'];
			$this->SetAutoPageBreak($tpl['o_AutoPageBreak'], $tpl['o_bMargin']);
			$this->FontFamily = $tpl['o_FontFamily'];
			$this->FontStyle  = $tpl['o_FontStyle'];
			$this->FontSizePt = $tpl['o_FontSizePt'];
			$this->FontSize   = $tpl['o_FontSize'];
			$fontKey          = $this->FontFamily . $this->FontStyle;
			if($fontKey)
				$this->CurrentFont =& $this->fonts[$fontKey];
			return $this->tpl;
		} else {
			return false;
		}
	}
	public function useTemplate($tplIdx, $x = null, $y = null, $w = 0, $h = 0) {
		if($this->page <= 0) {
			throw new LogicException('You have to add at least a page first!');
		}
		if(!isset($this->_tpls[$tplIdx])) {
			throw new InvalidArgumentException('Template does not exist!');
		}
		if($this->_inTpl) {
			$this->_res['tpl'][$this->tpl]['tpls'][$tplIdx] =& $this->_tpls[$tplIdx];
		}
		$tpl = $this->_tpls[$tplIdx];
		$_w  = $tpl['w'];
		$_h  = $tpl['h'];
		if($x == null) {
			$x = 0;
		}
		if($y == null) {
			$y = 0;
		}
		$x += $tpl['x'];
		$y += $tpl['y'];
		$wh      = $this->getTemplateSize($tplIdx, $w, $h);
		$w       = $wh['w'];
		$h       = $wh['h'];
		$tplData = array(
			'x' => $this->x,
			'y' => $this->y,
			'w' => $w,
			'h' => $h,
			'scaleX' => ($w / $_w),
			'scaleY' => ($h / $_h),
			'tx' => $x,
			'ty' => ($this->h - $y - $h),
			'lty' => ($this->h - $y - $h) - ($this->h - $_h) * ($h / $_h)
		);
		$this->_out(sprintf('q %.4F 0 0 %.4F %.4F %.4F cm', $tplData['scaleX'], $tplData['scaleY'], $tplData['tx'] * $this->k, $tplData['ty'] * $this->k));
		$this->_out(sprintf('%s%d Do Q', $this->tplPrefix, $tplIdx));
		$this->lastUsedTemplateData = $tplData;
		return array(
			'w' => $w,
			'h' => $h
		);
	}
	public function getTemplateSize($tplIdx, $w = 0, $h = 0) {
		if(!isset($this->_tpls[$tplIdx]))
			return false;
		$tpl = $this->_tpls[$tplIdx];
		$_w  = $tpl['w'];
		$_h  = $tpl['h'];
		if($w == 0 && $h == 0) {
			$w = $_w;
			$h = $_h;
		}
		if($w == 0)
			$w = $h * $_w / $_h;
		if($h == 0)
			$h = $w * $_h / $_w;
		return array(
			"w" => $w,
			"h" => $h
		);
	}
	public function SetFont($family, $style = '', $size = null, $fontfile = '', $subset = 'default', $out = true) {
		if(is_subclass_of($this, 'TCPDF')) {
			$args = func_get_args();
			return call_user_func_array(array(
				$this,
				'TCPDF::SetFont'
			), $args);
		}
		parent::SetFont($family, $style, $size);
		$fontkey = $this->FontFamily . $this->FontStyle;
		if($this->_inTpl) {
			$this->_res['tpl'][$this->tpl]['fonts'][$fontkey] =& $this->fonts[$fontkey];
		} else {
			$this->_res['page'][$this->page]['fonts'][$fontkey] =& $this->fonts[$fontkey];
		}
	}
	public function Image($file, $x = '', $y = '', $w = 0, $h = 0, $type = '', $link = '', $align = '', $resize = false, $dpi = 300, $palign = '', $ismask = false, $imgmask = false, $border = 0, $fitbox = false, $hidden = false, $fitonpage = false, $alt = false, $altimgs = array()) {
		if(is_subclass_of($this, 'TCPDF')) {
			$args = func_get_args();
			return call_user_func_array(array(
				$this,
				'TCPDF::Image'
			), $args);
		}
		$ret = parent::Image($file, $x, $y, $w, $h, $type, $link);
		if($this->_inTpl) {
			$this->_res['tpl'][$this->tpl]['images'][$file] =& $this->images[$file];
		} else {
			$this->_res['page'][$this->page]['images'][$file] =& $this->images[$file];
		}
		return $ret;
	}
	public function AddPage($orientation = '', $format = '', $keepmargins = false, $tocpage = false) {
		if(is_subclass_of($this, 'TCPDF')) {
			$args = func_get_args();
			return call_user_func_array(array(
				$this,
				'TCPDF::AddPage'
			), $args);
		}
		if($this->_inTpl) {
			throw new LogicException('Adding pages in templates is not possible!');
		}
		parent::AddPage($orientation, $format);
	}
	public function Link($x, $y, $w, $h, $link, $spaces = 0) {
		if(is_subclass_of($this, 'TCPDF')) {
			$args = func_get_args();
			return call_user_func_array(array(
				$this,
				'TCPDF::Link'
			), $args);
		}
		if($this->_inTpl) {
			throw new LogicException('Using links in templates is not posible!');
		}
		parent::Link($x, $y, $w, $h, $link);
	}
	public function AddLink() {
		if(is_subclass_of($this, 'TCPDF')) {
			$args = func_get_args();
			return call_user_func_array(array(
				$this,
				'TCPDF::AddLink'
			), $args);
		}
		if($this->_inTpl) {
			throw new LogicException('Adding links in templates is not possible!');
		}
		return parent::AddLink();
	}
	public function SetLink($link, $y = 0, $page = -1) {
		if(is_subclass_of($this, 'TCPDF')) {
			$args = func_get_args();
			return call_user_func_array(array(
				$this,
				'TCPDF::SetLink'
			), $args);
		}
		if($this->_inTpl) {
			throw new LogicException('Setting links in templates is not possible!');
		}
		parent::SetLink($link, $y, $page);
	}
	protected function _putformxobjects() {
		$filter = ($this->compress) ? '/Filter /FlateDecode ' : '';
		reset($this->_tpls);
		foreach($this->_tpls AS $tplIdx => $tpl) {
			$this->_newobj();
			$this->_tpls[$tplIdx]['n'] = $this->n;
			$this->_out('<<' . $filter . '/Type /XObject');
			$this->_out('/Subtype /Form');
			$this->_out('/FormType 1');
			$this->_out(sprintf('/BBox [%.2F %.2F %.2F %.2F]', $tpl['x'] * $this->k, -$tpl['y'] * $this->k, ($tpl['w'] + $tpl['x']) * $this->k, ($tpl['h'] - $tpl['y']) * $this->k));
			if($tpl['x'] != 0 || $tpl['y'] != 0) {
				$this->_out(sprintf('/Matrix [1 0 0 1 %.5F %.5F]', -$tpl['x'] * $this->k * 2, $tpl['y'] * $this->k * 2));
			}
			$this->_out('/Resources ');
			$this->_out('<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
			if(isset($this->_res['tpl'][$tplIdx])) {
				$res = $this->_res['tpl'][$tplIdx];
				if(isset($res['fonts']) && count($res['fonts'])) {
					$this->_out('/Font <<');
					foreach($res['fonts'] as $font) {
						$this->_out('/F' . $font['i'] . ' ' . $font['n'] . ' 0 R');
					}
					$this->_out('>>');
				}
				if(isset($res['images']) || isset($res['tpls'])) {
					$this->_out('/XObject <<');
					if(isset($res['images'])) {
						foreach($res['images'] as $image)
							$this->_out('/I' . $image['i'] . ' ' . $image['n'] . ' 0 R');
					}
					if(isset($res['tpls'])) {
						foreach($res['tpls'] as $i => $_tpl)
							$this->_out($this->tplPrefix . $i . ' ' . $_tpl['n'] . ' 0 R');
					}
					$this->_out('>>');
				}
			}
			$this->_out('>>');
			$buffer = ($this->compress) ? gzcompress($tpl['buffer']) : $tpl['buffer'];
			$this->_out('/Length ' . strlen($buffer) . ' >>');
			$this->_putstream($buffer);
			$this->_out('endobj');
		}
	}
	public function _putimages() {
		parent::_putimages();
		$this->_putformxobjects();
	}
	public function _putxobjectdict() {
		parent::_putxobjectdict();
		foreach($this->_tpls as $tplIdx => $tpl) {
			$this->_out(sprintf('%s%d %d 0 R', $this->tplPrefix, $tplIdx, $tpl['n']));
		}
	}
	public function _out($s) {
		if($this->state == 2 && $this->_inTpl) {
			$this->_tpls[$this->tpl]['buffer'] .= $s . "\n";
		} else {
			parent::_out($s);
		}
	}
}
class FPDI            extends FPDF_TPL {
	const VERSION = '1.5.3';
	public $currentFilename;
	public $parsers = array();
	public $currentParser;
	public $lastUsedPageBox;
	protected $_objStack;
	protected $_doneObjStack;
	protected $_currentObjId;
	protected $_importedPages = array();
	public function setSourceFile($filename) {
		$_filename = realpath($filename);
		if(false !== $_filename)
			$filename = $_filename;
		$this->currentFilename = $filename;
		if(!isset($this->parsers[$filename])) {
			$this->parsers[$filename] = $this->_getPdfParser($filename);
			$this->setPdfVersion(max($this->getPdfVersion(), $this->parsers[$filename]->getPdfVersion()));
		}
		$this->currentParser = $this->parsers[$filename];
		return $this->parsers[$filename]->getPageCount();
	}
	protected function _getPdfParser($filename) {
		return new fpdi_pdf_parser($filename);
	}
	public function getPdfVersion() {
		return $this->PDFVersion;
	}
	public function setPdfVersion($version = '1.3') {
		$this->PDFVersion = sprintf('%.1F', $version);
	}
	public function importPage($pageNo, $boxName = 'CropBox', $groupXObject = true) {
		if($this->_inTpl) {
			throw new LogicException('Please import the desired pages before creating a new template.');
		}
		$fn      = $this->currentFilename;
		$boxName = '/' . ltrim($boxName, '/');
		$pageKey = $fn . '-' . ((int) $pageNo) . $boxName;
		if(isset($this->_importedPages[$pageKey])) {
			return $this->_importedPages[$pageKey];
		}
		$parser = $this->parsers[$fn];
		$parser->setPageNo($pageNo);
		if(!in_array($boxName, $parser->availableBoxes)) {
			throw new InvalidArgumentException(sprintf('Unknown box: %s', $boxName));
		}
		$pageBoxes = $parser->getPageBoxes($pageNo, $this->k);
		if(!isset($pageBoxes[$boxName]) && ($boxName == '/BleedBox' || $boxName == '/TrimBox' || $boxName == '/ArtBox'))
			$boxName = '/CropBox';
		if(!isset($pageBoxes[$boxName]) && $boxName == '/CropBox')
			$boxName = '/MediaBox';
		if(!isset($pageBoxes[$boxName]))
			return false;
		$this->lastUsedPageBox = $boxName;
		$box                   = $pageBoxes[$boxName];
		$this->tpl++;
		$this->_tpls[$this->tpl] = array();
		$tpl =& $this->_tpls[$this->tpl];
		$tpl['parser']       = $parser;
		$tpl['resources']    = $parser->getPageResources();
		$tpl['buffer']       = $parser->getContent();
		$tpl['box']          = $box;
		$tpl['groupXObject'] = $groupXObject;
		if($groupXObject) {
			$this->setPdfVersion(max($this->getPdfVersion(), 1.4));
		}
		$this->_tpls[$this->tpl] = array_merge($this->_tpls[$this->tpl], $box);
		$tpl['x']                = 0;
		$tpl['y']                = 0;
		$rotation                = $parser->getPageRotation($pageNo);
		$tpl['_rotationAngle']   = 0;
		if(isset($rotation[1]) && ($angle = $rotation[1] % 360) != 0) {
			$steps    = $angle / 90;
			$_w       = $tpl['w'];
			$_h       = $tpl['h'];
			$tpl['w'] = $steps % 2 == 0 ? $_w : $_h;
			$tpl['h'] = $steps % 2 == 0 ? $_h : $_w;
			if($angle < 0)
				$angle += 360;
			$tpl['_rotationAngle'] = $angle * -1;
		}
		$this->_importedPages[$pageKey] = $this->tpl;
		return $this->tpl;
	}
	public function getLastUsedPageBox() {
		return $this->lastUsedPageBox;
	}
	public function useTemplate($tplIdx, $x = null, $y = null, $w = 0, $h = 0, $adjustPageSize = false) {
		if($adjustPageSize == true && is_null($x) && is_null($y)) {
			$size        = $this->getTemplateSize($tplIdx, $w, $h);
			$orientation = $size['w'] > $size['h'] ? 'L' : 'P';
			$size        = array(
				$size['w'],
				$size['h']
			);
			if(is_subclass_of($this, 'TCPDF')) {
				$this->setPageFormat($size, $orientation);
			} else {
				$size = $this->_getpagesize($size);
				if($orientation != $this->CurOrientation || $size[0] != $this->CurPageSize[0] || $size[1] != $this->CurPageSize[1]) {
					if($orientation == 'P') {
						$this->w = $size[0];
						$this->h = $size[1];
					} else {
						$this->w = $size[1];
						$this->h = $size[0];
					}
					$this->wPt                    = $this->w * $this->k;
					$this->hPt                    = $this->h * $this->k;
					$this->PageBreakTrigger       = $this->h - $this->bMargin;
					$this->CurOrientation         = $orientation;
					$this->CurPageSize            = $size;
					$this->PageSizes[$this->page] = array(
						$this->wPt,
						$this->hPt
					);
				}
			}
		}
		$this->_out('q 0 J 1 w 0 j 0 G 0 g');
		$size = parent::useTemplate($tplIdx, $x, $y, $w, $h);
		$this->_out('Q');
		return $size;
	}
	protected function _putimportedobjects() {
		foreach($this->parsers AS $filename => $p) {
			$this->currentParser = $p;
			if(!isset($this->_objStack[$filename]) || !is_array($this->_objStack[$filename])) {
				continue;
			}
			while(($n = key($this->_objStack[$filename])) !== null) {
				try {
					$nObj = $this->currentParser->resolveObject($this->_objStack[$filename][$n][1]);
				}
				catch(Exception $e) {
					$nObj = array(
						pdf_parser::TYPE_OBJECT,
						pdf_parser::TYPE_NULL
					);
				}
				$this->_newobj($this->_objStack[$filename][$n][0]);
				if($nObj[0] == pdf_parser::TYPE_STREAM) {
					$this->_writeValue($nObj);
				} else {
					$this->_writeValue($nObj[1]);
				}
				$this->_out("\nendobj");
				$this->_objStack[$filename][$n] = null;
				unset($this->_objStack[$filename][$n]);
				reset($this->_objStack[$filename]);
			}
		}
	}
	protected function _putformxobjects() {
		$filter = ($this->compress) ? '/Filter /FlateDecode ' : '';
		reset($this->_tpls);
		foreach($this->_tpls AS $tplIdx => $tpl) {
			$this->_newobj();
			$currentN                  = $this->n;
			$this->_tpls[$tplIdx]['n'] = $this->n;
			$this->_out('<<' . $filter . '/Type /XObject');
			$this->_out('/Subtype /Form');
			$this->_out('/FormType 1');
			$this->_out(sprintf('/BBox [%.2F %.2F %.2F %.2F]', (isset($tpl['box']['llx']) ? $tpl['box']['llx'] : $tpl['x']) * $this->k, (isset($tpl['box']['lly']) ? $tpl['box']['lly'] : -$tpl['y']) * $this->k, (isset($tpl['box']['urx']) ? $tpl['box']['urx'] : $tpl['w'] + $tpl['x']) * $this->k, (isset($tpl['box']['ury']) ? $tpl['box']['ury'] : $tpl['h'] - $tpl['y']) * $this->k));
			$c  = 1;
			$s  = 0;
			$tx = 0;
			$ty = 0;
			if(isset($tpl['box'])) {
				$tx = -$tpl['box']['llx'];
				$ty = -$tpl['box']['lly'];
				if($tpl['_rotationAngle'] <> 0) {
					$angle = $tpl['_rotationAngle'] * M_PI / 180;
					$c     = cos($angle);
					$s     = sin($angle);
					switch($tpl['_rotationAngle']) {
						case -90:
							$tx = -$tpl['box']['lly'];
							$ty = $tpl['box']['urx'];
							break;
						case -180:
							$tx = $tpl['box']['urx'];
							$ty = $tpl['box']['ury'];
							break;
						case -270:
							$tx = $tpl['box']['ury'];
							$ty = -$tpl['box']['llx'];
							break;
					}
				}
			} else if($tpl['x'] != 0 || $tpl['y'] != 0) {
				$tx = -$tpl['x'] * 2;
				$ty = $tpl['y'] * 2;
			}
			$tx *= $this->k;
			$ty *= $this->k;
			if($c != 1 || $s != 0 || $tx != 0 || $ty != 0) {
				$this->_out(sprintf('/Matrix [%.5F %.5F %.5F %.5F %.5F %.5F]', $c, $s, -$s, $c, $tx, $ty));
			}
			$this->_out('/Resources ');
			if(isset($tpl['resources'])) {
				$this->currentParser = $tpl['parser'];
				$this->_writeValue($tpl['resources']);
			} else {
				$this->_out('<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
				if(isset($this->_res['tpl'][$tplIdx])) {
					$res = $this->_res['tpl'][$tplIdx];
					if(isset($res['fonts']) && count($res['fonts'])) {
						$this->_out('/Font <<');
						foreach($res['fonts'] as $font)
							$this->_out('/F' . $font['i'] . ' ' . $font['n'] . ' 0 R');
						$this->_out('>>');
					}
					if(isset($res['images']) && count($res['images']) || isset($res['tpls']) && count($res['tpls'])) {
						$this->_out('/XObject <<');
						if(isset($res['images'])) {
							foreach($res['images'] as $image)
								$this->_out('/I' . $image['i'] . ' ' . $image['n'] . ' 0 R');
						}
						if(isset($res['tpls'])) {
							foreach($res['tpls'] as $i => $_tpl)
								$this->_out($this->tplPrefix . $i . ' ' . $_tpl['n'] . ' 0 R');
						}
						$this->_out('>>');
					}
					$this->_out('>>');
				}
			}
			if(isset($tpl['groupXObject']) && $tpl['groupXObject']) {
				$this->_out('/Group <</Type/Group/S/Transparency>>');
			}
			$newN    = $this->n;
			$this->n = $currentN;
			$buffer  = ($this->compress) ? gzcompress($tpl['buffer']) : $tpl['buffer'];
			if(is_subclass_of($this, 'TCPDF')) {
				$buffer = $this->_getrawstream($buffer);
				$this->_out('/Length ' . strlen($buffer) . ' >>');
				$this->_out("stream\n" . $buffer . "\nendstream");
			} else {
				$this->_out('/Length ' . strlen($buffer) . ' >>');
				$this->_putstream($buffer);
			}
			$this->_out('endobj');
			$this->n = $newN;
		}
		$this->_putimportedobjects();
	}
	public function _newobj($objId = false, $onlyNewObj = false) {
		if(!$objId) {
			$objId = ++$this->n;
		}
		if(!$onlyNewObj) {
			$this->offsets[$objId] = is_subclass_of($this, 'TCPDF') ? $this->bufferlen : strlen($this->buffer);
			$this->_out($objId . ' 0 obj');
			$this->_currentObjId = $objId;
		}
		return $objId;
	}
	protected function _writeValue(&$value) {
		if(is_subclass_of($this, 'TCPDF')) {
			parent::_prepareValue($value);
		}
		switch($value[0]) {
			case pdf_parser::TYPE_TOKEN:
				$this->_straightOut($value[1] . ' ');
				break;
			case pdf_parser::TYPE_NUMERIC:
			case pdf_parser::TYPE_REAL:
				if(is_float($value[1]) && $value[1] != 0) {
					$this->_straightOut(rtrim(rtrim(sprintf('%F', $value[1]), '0'), '.') . ' ');
				} else {
					$this->_straightOut($value[1] . ' ');
				}
				break;
			case pdf_parser::TYPE_ARRAY:
				$this->_straightOut('[');
				for($i = 0; $i < count($value[1]); $i++) {
					$this->_writeValue($value[1][$i]);
				}
				$this->_out(']');
				break;
			case pdf_parser::TYPE_DICTIONARY:
				$this->_straightOut('<<');
				reset($value[1]);
				while(list($k, $v) = each($value[1])) {
					$this->_straightOut($k . ' ');
					$this->_writeValue($v);
				}
				$this->_straightOut('>>');
				break;
			case pdf_parser::TYPE_OBJREF:
				$cpfn =& $this->currentParser->filename;
				if(!isset($this->_doneObjStack[$cpfn][$value[1]])) {
					$this->_newobj(false, true);
					$this->_objStack[$cpfn][$value[1]]     = array(
						$this->n,
						$value
					);
					$this->_doneObjStack[$cpfn][$value[1]] = array(
						$this->n,
						$value
					);
				}
				$objId = $this->_doneObjStack[$cpfn][$value[1]][0];
				$this->_out($objId . ' 0 R');
				break;
			case pdf_parser::TYPE_STRING:
				$this->_straightOut('(' . $value[1] . ')');
				break;
			case pdf_parser::TYPE_STREAM:
				$this->_writeValue($value[1]);
				$this->_out('stream');
				$this->_out($value[2][1]);
				$this->_straightOut("endstream");
				break;
			case pdf_parser::TYPE_HEX:
				$this->_straightOut('<' . $value[1] . '>');
				break;
			case pdf_parser::TYPE_BOOLEAN:
				$this->_straightOut($value[1] ? 'true ' : 'false ');
				break;
			case pdf_parser::TYPE_NULL:
				$this->_straightOut('null ');
				break;
		}
	}
	protected function _straightOut($s) {
		if(!is_subclass_of($this, 'TCPDF')) {
			if($this->state == 2) {
				$this->pages[$this->page] .= $s;
			} else {
				$this->buffer .= $s;
			}
		} else {
			if($this->state == 2) {
				if($this->inxobj) {
					$this->xobjects[$this->xobjid]['outdata'] .= $s;
				} else if((!$this->InFooter) AND isset($this->footerlen[$this->page]) AND ($this->footerlen[$this->page] > 0)) {
					$pagebuff = $this->getPageBuffer($this->page);
					$page     = substr($pagebuff, 0, -$this->footerlen[$this->page]);
					$footer   = substr($pagebuff, -$this->footerlen[$this->page]);
					$this->setPageBuffer($this->page, $page . $s . $footer);
					$this->footerpos[$this->page] += strlen($s);
				} else {
					$this->setPageBuffer($this->page, $s, true);
				}
			} else if($this->state > 0) {
				$this->setBuffer($s);
			}
		}
	}
	public function _enddoc() {
		parent::_enddoc();
		$this->_closeParsers();
	}
	protected function _closeParsers() {
		if($this->state > 2) {
			$this->cleanUp();
			return true;
		}
		return false;
	}
	public function cleanUp() {
		while(($parser = array_pop($this->parsers)) !== null) {
			$parser->closeFile();
		}
	}
}
class PDF_HTML        extends FPDI     {
	var $B;
	var $I;
	var $U;
	var $HREF;
	var $fontList;
	var $issetfont;
	var $issetcolor;
	function __construct($orientation = 'P', $unit = 'mm', $format = 'A4') {
		parent::__construct($orientation, $unit, $format);
		$this->B          = 0;
		$this->I          = 0;
		$this->U          = 0;
		$this->HREF       = '';
		$this->issetfont  = false;
		$this->issetcolor = false;
	}
	function WriteHTML($html) {
		$html = strip_tags($html, "<b><u><i><a><img><p><br><strong><em><font><tr><blockquote>");
		$html = str_replace("\n", ' ', $html);
		$a    = preg_split('/<(.*)>/U', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
		foreach($a as $i => $e) {
			if($i % 2 == 0) {
				if($this->HREF)
					$this->PutLink($this->HREF, $e);
				else
					$this->Write(5, stripslashes(self::txtentities($e)));
			} else {
				if($e[0] == '/')
					$this->CloseTag(strtoupper(substr($e, 1)));
				else {
					$a2   = explode(' ', $e);
					$tag  = strtoupper(array_shift($a2));
					$attr = array();
					foreach($a2 as $v) {
						if(preg_match('/([^=]*)=["\']?([^"\']*)/', $v, $a3))
							$attr[strtoupper($a3[1])] = $a3[2];
					}
					$this->OpenTag($tag, $attr);
				}
			}
		}
	}
	function OpenTag($tag, $attr) {
		switch($tag) {
			case 'STRONG':
				$this->SetStyle('B', true);
				break;
			case 'EM':
				$this->SetStyle('I', true);
				break;
			case 'B':
			case 'I':
			case 'U':
				$this->SetStyle($tag, true);
				break;
			case 'A':
				$this->HREF = $attr['HREF'];
				break;
			case 'IMG':
				if(isset($attr['SRC']) && (isset($attr['WIDTH']) || isset($attr['HEIGHT']))) {
					if(!isset($attr['WIDTH']))
						$attr['WIDTH'] = 0;
					if(!isset($attr['HEIGHT']))
						$attr['HEIGHT'] = 0;
					$this->Image($attr['SRC'], $this->GetX(), $this->GetY(), self::px2mm($attr['WIDTH']), self::px2mm($attr['HEIGHT']));
				}
				break;
			case 'TR':
			case 'BLOCKQUOTE':
			case 'BR':
				$this->Ln(5);
				break;
			case 'P':
				$this->Ln(10);
				break;
			case 'FONT':
				if(isset($attr['COLOR']) && $attr['COLOR'] != '') {
					$coul = self::hex2dec($attr['COLOR']);
					$this->SetTextColor($coul['R'], $coul['V'], $coul['B']);
					$this->issetcolor = true;
				}
				if(isset($attr['FACE']) && in_array(strtolower($attr['FACE']), $this->fontlist)) {
					$this->SetFont(strtolower($attr['FACE']));
					$this->issetfont = true;
				}
				break;
		}
	}
	function CloseTag($tag) {
		if($tag == 'STRONG')
			$tag = 'B';
		if($tag == 'EM')
			$tag = 'I';
		if($tag == 'B' || $tag == 'I' || $tag == 'U')
			$this->SetStyle($tag, false);
		if($tag == 'A')
			$this->HREF = '';
		if($tag == 'FONT') {
			if($this->issetcolor == true) {
				$this->SetTextColor(0);
			}
			if($this->issetfont) {
				$this->SetFont('arial');
				$this->issetfont = false;
			}
		}
	}
	function SetStyle($tag, $enable) {
		$this->$tag += ($enable ? 1 : -1);
		$style = '';
		foreach(array(
			'B',
			'I',
			'U'
		) as $s) {
			if($this->$s > 0)
				$style .= $s;
		}
		$this->SetFont('', $style);
	}
	function PutLink($URL, $txt) {
		$this->SetTextColor(0, 0, 255);
		$this->SetStyle('U', true);
		$this->Write(5, $txt, $URL);
		$this->SetStyle('U', false);
		$this->SetTextColor(0);
	}
	
	static function hex2dec($couleur = "#000000") {
		$R                = substr($couleur, 1, 2);
		$rouge            = hexdec($R);
		$V                = substr($couleur, 3, 2);
		$vert             = hexdec($V);
		$B                = substr($couleur, 5, 2);
		$bleu             = hexdec($B);
		$tbl_couleur      = array();
		$tbl_couleur['R'] = $rouge;
		$tbl_couleur['V'] = $vert;
		$tbl_couleur['B'] = $bleu;
		return $tbl_couleur;
	}
	static function px2mm($px) {
		return $px * 25.4 / 72;
	}
	static function txtentities($html) {
		$trans = get_html_translation_table(HTML_ENTITIES);
		$trans = array_flip($trans);
		return strtr($html, $trans);
	}
}

class fpdi_pdf_parser extends pdf_parser {
	protected $_pages;
	protected $_pageCount;
	public $pageNo;
	public $_pdfVersion;
	public $availableBoxes = array('/MediaBox', '/CropBox', '/BleedBox', '/TrimBox', '/ArtBox');
	public function __construct($filename) {
		parent::__construct($filename);
		$pages = $this->resolveObject($this->_root[1][1]['/Pages']);
		$this->_readPages($pages, $this->_pages);
		$this->_pageCount = count($this->_pages);
	}
	public function getPageCount() {
		return $this->_pageCount;
	}
	public function setPageNo($pageNo) {
		$pageNo = ((int) $pageNo) - 1;
		if($pageNo < 0 || $pageNo >= $this->getPageCount()) {
			throw new InvalidArgumentException('Invalid page number!');
		}
		$this->pageNo = $pageNo;
	}
	public function getPageResources() {
		return $this->_getPageResources($this->_pages[$this->pageNo]);
	}
	protected function _getPageResources($obj) {
		$obj = $this->resolveObject($obj);
		if(isset($obj[1][1]['/Resources'])) {
			$res = $this->resolveObject($obj[1][1]['/Resources']);
			if($res[0] == pdf_parser::TYPE_OBJECT)
				return $res[1];
			return $res;
		}
		if(!isset($obj[1][1]['/Parent'])) {
			return false;
		}
		$res = $this->_getPageResources($obj[1][1]['/Parent']);
		if($res[0] == pdf_parser::TYPE_OBJECT)
			return $res[1];
		return $res;
	}
	public function getContent() {
		$buffer = '';
		if(isset($this->_pages[$this->pageNo][1][1]['/Contents'])) {
			$contents = $this->_getPageContent($this->_pages[$this->pageNo][1][1]['/Contents']);
			foreach($contents AS $tmpContent) {
				$buffer .= $this->_unFilterStream($tmpContent) . ' ';
			}
		}
		return $buffer;
	}
	protected function _getPageContent($contentRef) {
		$contents = array();
		if($contentRef[0] == pdf_parser::TYPE_OBJREF) {
			$content = $this->resolveObject($contentRef);
			if($content[1][0] == pdf_parser::TYPE_ARRAY) {
				$contents = $this->_getPageContent($content[1]);
			} else {
				$contents[] = $content;
			}
		} else if($contentRef[0] == pdf_parser::TYPE_ARRAY) {
			foreach($contentRef[1] AS $tmp_content_ref) {
				$contents = array_merge($contents, $this->_getPageContent($tmp_content_ref));
			}
		}
		return $contents;
	}
	protected function _getPageBox($page, $boxIndex, $k) {
		$page = $this->resolveObject($page);
		$box  = null;
		if(isset($page[1][1][$boxIndex])) {
			$box = $page[1][1][$boxIndex];
		}
		if(!is_null($box) && $box[0] == pdf_parser::TYPE_OBJREF) {
			$tmp_box = $this->resolveObject($box);
			$box     = $tmp_box[1];
		}
		if(!is_null($box) && $box[0] == pdf_parser::TYPE_ARRAY) {
			$b = $box[1];
			return array(
				'x' => $b[0][1] / $k,
				'y' => $b[1][1] / $k,
				'w' => abs($b[0][1] - $b[2][1]) / $k,
				'h' => abs($b[1][1] - $b[3][1]) / $k,
				'llx' => min($b[0][1], $b[2][1]) / $k,
				'lly' => min($b[1][1], $b[3][1]) / $k,
				'urx' => max($b[0][1], $b[2][1]) / $k,
				'ury' => max($b[1][1], $b[3][1]) / $k
			);
		} else if(!isset($page[1][1]['/Parent'])) {
			return false;
		} else {
			return $this->_getPageBox($this->resolveObject($page[1][1]['/Parent']), $boxIndex, $k);
		}
	}
	public function getPageBoxes($pageNo, $k) {
		if(!isset($this->_pages[$pageNo - 1])) {
			throw new InvalidArgumentException('Page ' . $pageNo . ' does not exists.');
		}
		return $this->_getPageBoxes($this->_pages[$pageNo - 1], $k);
	}
	protected function _getPageBoxes($page, $k) {
		$boxes = array();
		foreach($this->availableBoxes AS $box) {
			if($_box = $this->_getPageBox($page, $box, $k)) {
				$boxes[$box] = $_box;
			}
		}
		return $boxes;
	}
	public function getPageRotation($pageNo) {
		if(!isset($this->_pages[$pageNo - 1])) {
			throw new InvalidArgumentException('Page ' . $pageNo . ' does not exists.');
		}
		return $this->_getPageRotation($this->_pages[$pageNo - 1]);
	}
	protected function _getPageRotation($obj) {
		$obj = $this->resolveObject($obj);
		if(isset($obj[1][1]['/Rotate'])) {
			$res = $this->resolveObject($obj[1][1]['/Rotate']);
			if($res[0] == pdf_parser::TYPE_OBJECT)
				return $res[1];
			return $res;
		}
		if(!isset($obj[1][1]['/Parent'])) {
			return false;
		}
		$res = $this->_getPageRotation($obj[1][1]['/Parent']);
		if($res[0] == pdf_parser::TYPE_OBJECT)
			return $res[1];
		return $res;
	}
	protected function _readPages(&$pages, &$result) {
		$_kids = $this->resolveObject($pages[1][1]['/Kids']);
		if(!is_array($_kids)) {
			throw new Exception('Cannot find /Kids in current /Page-Dictionary');
		}
		if($_kids[0] === self::TYPE_OBJECT) {
			$_kids = $_kids[1];
		}
		$kids = $_kids[1];
		foreach($kids as $v) {
			$pg = $this->resolveObject($v);
			if($pg[1][1]['/Type'][1] === '/Pages') {
				$this->_readPages($pg, $result);
			} else {
				$result[] = $pg;
			}
		}
	}
}
class pdf_parser    {
	const TYPE_NULL = 0;
	const TYPE_NUMERIC = 1;
	const TYPE_TOKEN = 2;
	const TYPE_HEX = 3;
	const TYPE_STRING = 4;
	const TYPE_DICTIONARY = 5;
	const TYPE_ARRAY = 6;
	const TYPE_OBJDEC = 7;
	const TYPE_OBJREF = 8;
	const TYPE_OBJECT = 9;
	const TYPE_STREAM = 10;
	const TYPE_BOOLEAN = 11;
	const TYPE_REAL = 12;
	static public $searchForStartxrefLength = 5500;
	public $filename;
	protected $_f;
	protected $_c;
	protected $_xref;
	protected $_root;
	protected $_pdfVersion;
	protected $_readPlain = true;
	protected $_currentObj;
	public function __construct($filename) {
		$this->filename = $filename;
		$this->_f       = @fopen($this->filename, 'rb');
		if(!$this->_f) {
			throw new InvalidArgumentException(sprintf('Cannot open %s !', $filename));
		}
		$this->getPdfVersion();
		$this->_c    = new pdf_context($this->_f);
		$this->_xref = array();
		$this->_readXref($this->_xref, $this->_findXref());
		$this->getEncryption();
		$this->_readRoot();
	}
	public function __destruct() {
		$this->closeFile();
	}
	public function closeFile() {
		if(isset($this->_f) && is_resource($this->_f)) {
			fclose($this->_f);
			unset($this->_f);
		}
	}
	public function getEncryption() {
		if(isset($this->_xref['trailer'][1]['/Encrypt'])) {
			throw new Exception('File is encrypted!');
		}
	}
	public function getPdfVersion() {
		if($this->_pdfVersion === null) {
			fseek($this->_f, 0);
			preg_match('/\d\.\d/', fread($this->_f, 16), $m);
			if(isset($m[0]))
				$this->_pdfVersion = $m[0];
		}
		return $this->_pdfVersion;
	}
	protected function _readRoot() {
		if($this->_xref['trailer'][1]['/Root'][0] != self::TYPE_OBJREF) {
			throw new Exception('Wrong Type of Root-Element! Must be an indirect reference');
		}
		$this->_root = $this->resolveObject($this->_xref['trailer'][1]['/Root']);
	}
	protected function _findXref() {
		$toRead = self::$searchForStartxrefLength;
		$stat   = fseek($this->_f, -$toRead, SEEK_END);
		if($stat === -1) {
			fseek($this->_f, 0);
		}
		$data       = fread($this->_f, $toRead);
		$keywordPos = strpos(strrev($data), strrev('startxref'));
		if(false === $keywordPos) {
			$keywordPos = strpos(strrev($data), strrev('startref'));
		}
		if(false === $keywordPos) {
			throw new Exception('Unable to find "startxref" keyword.');
		}
		$pos  = strlen($data) - $keywordPos;
		$data = substr($data, $pos);
		if(!preg_match('/\s*(\d+).*$/s', $data, $matches)) {
			throw new Exception('Unable to find pointer to xref table.');
		}
		return (int) $matches[1];
	}
	protected function _readXref(&$result, $offset) {
		$tempPos = $offset - min(20, $offset);
		fseek($this->_f, $tempPos);
		$data    = fread($this->_f, 100);
		$xrefPos = strrpos($data, 'xref');
		if($xrefPos === false) {
			$this->_c->reset($offset);
			$xrefStreamObjDec = $this->_readValue($this->_c);
			if(is_array($xrefStreamObjDec) && isset($xrefStreamObjDec[0]) && $xrefStreamObjDec[0] == self::TYPE_OBJDEC) {
				throw new Exception(sprintf('This document (%s) probably uses a compression technique which is not supported by the ' . 'free parser shipped with FPDI. (See https://www.setasign.com/fpdi-pdf-parser for more details)', $this->filename));
			} else {
				throw new Exception('Unable to find xref table.');
			}
		}
		if(!isset($result['xrefLocation'])) {
			$result['xrefLocation'] = $tempPos + $xrefPos;
			$result['maxObject']    = 0;
		}
		$cycles        = -1;
		$bytesPerCycle = 100;
		fseek($this->_f, $tempPos = $tempPos + $xrefPos + 4);
		$data = fread($this->_f, $bytesPerCycle);
		while(($trailerPos = strpos($data, 'trailer', max($bytesPerCycle * $cycles++, 0))) === false && !feof($this->_f)) {
			$data .= fread($this->_f, $bytesPerCycle);
		}
		if($trailerPos === false) {
			throw new Exception('Trailer keyword not found after xref table');
		}
		$data  = ltrim(substr($data, 0, $trailerPos));
		$found = preg_match_all("/(\r\n|\n|\r)/", substr($data, 0, 100), $m);
		if($found === 0) {
			throw new Exception('Xref table seems to be corrupted.');
		}
		$differentLineEndings = count(array_unique($m[0]));
		if($differentLineEndings > 1) {
			$lines = preg_split("/(\r\n|\n|\r)/", $data, -1, PREG_SPLIT_NO_EMPTY);
		} else {
			$lines = explode($m[0][0], $data);
		}
		$data = $differentLineEndings = $m = null;
		unset($data, $differentLineEndings, $m);
		$linesCount = count($lines);
		$start      = 1;
		for($i = 0; $i < $linesCount; $i++) {
			$line = trim($lines[$i]);
			if($line) {
				$pieces = explode(' ', $line);
				$c      = count($pieces);
				switch($c) {
					case 2:
						$start = (int) $pieces[0];
						$end   = $start + (int) $pieces[1];
						if($end > $result['maxObject'])
							$result['maxObject'] = $end;
						break;
					case 3:
						if(!isset($result['xref'][$start]))
							$result['xref'][$start] = array();
						if(!array_key_exists($gen = (int) $pieces[1], $result['xref'][$start])) {
							$result['xref'][$start][$gen] = $pieces[2] == 'n' ? (int) $pieces[0] : null;
						}
						$start++;
						break;
					default:
						throw new Exception('Unexpected data in xref table');
				}
			}
		}
		$lines = $pieces = $line = $start = $end = $gen = null;
		unset($lines, $pieces, $line, $start, $end, $gen);
		$this->_c->reset($tempPos + $trailerPos + 7);
		$trailer = $this->_readValue($this->_c);
		if(!isset($result['trailer'])) {
			$result['trailer'] = $trailer;
		}
		if(isset($trailer[1]['/Prev'])) {
			$this->_readXref($result, $trailer[1]['/Prev'][1]);
		}
		$trailer = null;
		unset($trailer);
		return true;
	}
	protected function _readValue(&$c, $token = null) {
		if(is_null($token)) {
			$token = $this->_readToken($c);
		}
		if($token === false) {
			return false;
		}
		switch($token) {
			case '<':
				$pos = $c->offset;
				while(1) {
					$match = strpos($c->buffer, '>', $pos);
					if($match === false) {
						if(!$c->increaseLength()) {
							return false;
						} else {
							continue;
						}
					}
					$result    = substr($c->buffer, $c->offset, $match - $c->offset);
					$c->offset = $match + 1;
					return array(
						self::TYPE_HEX,
						$result
					);
				}
				break;
			case '<<':
				$result = array();
				while(($key = $this->_readToken($c)) !== '>>') {
					if($key === false) {
						return false;
					}
					if(($value = $this->_readValue($c)) === false) {
						return false;
					}
					if($value[0] == self::TYPE_TOKEN && $value[1] == '>>') {
						$result[$key] = array(
							self::TYPE_NULL
						);
						break;
					}
					$result[$key] = $value;
				}
				return array(
					self::TYPE_DICTIONARY,
					$result
				);
			case '[':
				$result = array();
				while(($token = $this->_readToken($c)) !== ']') {
					if($token === false) {
						return false;
					}
					if(($value = $this->_readValue($c, $token)) === false) {
						return false;
					}
					$result[] = $value;
				}
				return array(
					self::TYPE_ARRAY,
					$result
				);
			case '(':
				$pos          = $c->offset;
				$openBrackets = 1;
				do {
					for(; $openBrackets != 0 && $pos < $c->length; $pos++) {
						switch(ord($c->buffer[$pos])) {
							case 0x28:
								$openBrackets++;
								break;
							case 0x29:
								$openBrackets--;
								break;
							case 0x5C:
								$pos++;
						}
					}
				} while($openBrackets != 0 && $c->increaseLength());
				$result    = substr($c->buffer, $c->offset, $pos - $c->offset - 1);
				$c->offset = $pos;
				return array(
					self::TYPE_STRING,
					$result
				);
			case 'stream':
				$tempPos    = $c->getPos() - strlen($c->buffer);
				$tempOffset = $c->offset;
				$c->reset($startPos = $tempPos + $tempOffset);
				while($c->buffer[0] !== chr(10) && $c->buffer[0] !== chr(13)) {
					$c->reset(++$startPos);
					if($c->ensureContent() === false) {
						throw new Exception('Unable to parse stream data. No newline followed the stream keyword.');
					}
				}
				$e = 0;
				if($c->buffer[0] == chr(10) || $c->buffer[0] == chr(13))
					$e++;
				if($c->buffer[1] == chr(10) && $c->buffer[0] != chr(10))
					$e++;
				if($this->_currentObj[1][1]['/Length'][0] == self::TYPE_OBJREF) {
					$tmpLength = $this->resolveObject($this->_currentObj[1][1]['/Length']);
					$length    = $tmpLength[1][1];
				} else {
					$length = $this->_currentObj[1][1]['/Length'][1];
				}
				if($length > 0) {
					$c->reset($startPos + $e, $length);
					$v = $c->buffer;
				} else {
					$v = '';
				}
				$c->reset($startPos + $e + $length);
				$endstream = $this->_readToken($c);
				if($endstream != 'endstream') {
					$c->reset($startPos + $e + $length + 9);
				}
				return array(
					self::TYPE_STREAM,
					$v
				);
			default:
				if(is_numeric($token)) {
					if(($tok2 = $this->_readToken($c)) !== false) {
						if(is_numeric($tok2)) {
							if(($tok3 = $this->_readToken($c)) !== false) {
								switch($tok3) {
									case 'obj':
										return array(
											self::TYPE_OBJDEC,
											(int) $token,
											(int) $tok2
										);
									case 'R':
										return array(
											self::TYPE_OBJREF,
											(int) $token,
											(int) $tok2
										);
								}
								array_push($c->stack, $tok3);
							}
						}
						array_push($c->stack, $tok2);
					}
					if($token === (string) ((int) $token))
						return array(
							self::TYPE_NUMERIC,
							(int) $token
						);
					else
						return array(
							self::TYPE_REAL,
							(float) $token
						);
				} else if($token == 'true' || $token == 'false') {
					return array(
						self::TYPE_BOOLEAN,
						$token == 'true'
					);
				} else if($token == 'null') {
					return array(
						self::TYPE_NULL
					);
				} else {
					return array(
						self::TYPE_TOKEN,
						$token
					);
				}
		}
	}
	public function resolveObject($objSpec) {
		$c = $this->_c;
		if(!is_array($objSpec)) {
			return false;
		}
		if($objSpec[0] == self::TYPE_OBJREF) {
			if(isset($this->_xref['xref'][$objSpec[1]][$objSpec[2]])) {
				$oldPos = $c->getPos();
				$c->reset($this->_xref['xref'][$objSpec[1]][$objSpec[2]]);
				$header = $this->_readValue($c);
				if($header[0] != self::TYPE_OBJDEC || $header[1] != $objSpec[1] || $header[2] != $objSpec[2]) {
					$toSearchFor = $objSpec[1] . ' ' . $objSpec[2] . ' obj';
					if(preg_match('/' . $toSearchFor . '/', $c->buffer)) {
						$c->offset = strpos($c->buffer, $toSearchFor) + strlen($toSearchFor);
						$c->stack  = array();
					} else {
						throw new Exception(sprintf("Unable to find object (%s, %s) at expected location.", $objSpec[1], $objSpec[2]));
					}
				}
				$result = array(
					self::TYPE_OBJECT,
					'obj' => $objSpec[1],
					'gen' => $objSpec[2]
				);
				$this->_currentObj =& $result;
				while(true) {
					$value = $this->_readValue($c);
					if($value === false || count($result) > 4) {
						break;
					}
					if($value[0] == self::TYPE_TOKEN && $value[1] === 'endobj') {
						break;
					}
					$result[] = $value;
				}
				$c->reset($oldPos);
				if(isset($result[2][0]) && $result[2][0] == self::TYPE_STREAM) {
					$result[0] = self::TYPE_STREAM;
				}
			} else {
				throw new Exception(sprintf("Unable to find object (%s, %s) at expected location.", $objSpec[1], $objSpec[2]));
			}
			return $result;
		} else {
			return $objSpec;
		}
	}
	protected function _readToken($c) {
		if(count($c->stack)) {
			return array_pop($c->stack);
		}
		do {
			if(!$c->ensureContent()) {
				return false;
			}
			$c->offset += strspn($c->buffer, "\x20\x0A\x0C\x0D\x09\x00", $c->offset);
		} while($c->offset >= $c->length - 1);
		$char = $c->buffer[$c->offset++];
		switch($char) {
			case '[':
			case ']':
			case '(':
			case ')':
				return $char;
			case '<':
			case '>':
				if($c->buffer[$c->offset] == $char) {
					if(!$c->ensureContent()) {
						return false;
					}
					$c->offset++;
					return $char . $char;
				} else {
					return $char;
				}
			case '%':
				$pos = $c->offset;
				while(1) {
					$match = preg_match("/(\r\n|\r|\n)/", $c->buffer, $m, PREG_OFFSET_CAPTURE, $pos);
					if($match === 0) {
						if(!$c->increaseLength()) {
							return false;
						} else {
							continue;
						}
					}
					$c->offset = $m[0][1] + strlen($m[0][0]);
					return $this->_readToken($c);
				}
			default:
				if(!$c->ensureContent()) {
					return false;
				}
				while(1) {
					$pos = strcspn($c->buffer, "\x20%[]<>()/\x0A\x0C\x0D\x09\x00", $c->offset);
					if($c->offset + $pos <= $c->length - 1) {
						break;
					} else {
						$c->increaseLength();
					}
				}
				$result = substr($c->buffer, $c->offset - 1, $pos + 1);
				$c->offset += $pos;
				return $result;
		}
	}
	protected function _unFilterStream($obj) {
		$filters = array();
		if(isset($obj[1][1]['/Filter'])) {
			$filter = $obj[1][1]['/Filter'];
			if($filter[0] == pdf_parser::TYPE_OBJREF) {
				$tmpFilter = $this->resolveObject($filter);
				$filter    = $tmpFilter[1];
			}
			if($filter[0] == pdf_parser::TYPE_TOKEN) {
				$filters[] = $filter;
			} else if($filter[0] == pdf_parser::TYPE_ARRAY) {
				$filters = $filter[1];
			}
		}
		$stream = $obj[2][1];
		foreach($filters AS $filter) {
			switch($filter[1]) {
				case '/FlateDecode':
				case '/Fl':
					if(function_exists('gzuncompress')) {
						$oStream = $stream;
						$stream  = (strlen($stream) > 0) ? @gzuncompress($stream) : '';
					} else {
						throw new Exception(sprintf('To handle %s filter, please compile php with zlib support.', $filter[1]));
					}
					if($stream === false) {
						$tries = 0;
						while($tries < 8 && ($stream === false || strlen($stream) < strlen($oStream))) {
							$oStream = substr($oStream, 1);
							$stream  = @gzinflate($oStream);
							$tries++;
						}
						if($stream === false) {
							throw new Exception('Error while decompressing stream.');
						}
					}
					break;
				case '/LZWDecode':
					$decoder = new FilterLZW();
					$stream  = $decoder->decode($stream);
					break;
				case '/ASCII85Decode':
					$decoder = new FilterASCII85();
					$stream  = $decoder->decode($stream);
					break;
				case '/ASCIIHexDecode':
					$decoder = new FilterASCIIHexDecode();
					$stream  = $decoder->decode($stream);
					break;
				case null:
					break;
				default:
					throw new Exception(sprintf('Unsupported Filter: %s', $filter[1]));
			}
		}
		return $stream;
	}
}
class pdf_context   {
	protected $_mode = 0;
	public $file;
	public $buffer;
	public $offset;
	public $length;
	public $stack;
	public function __construct(&$f) {
		$this->file =& $f;
		if(is_string($this->file))
			$this->_mode = 1;
		$this->reset();
	}
	public function getPos() {
		if($this->_mode == 0) {
			return ftell($this->file);
		} else {
			return 0;
		}
	}
	public function reset($pos = null, $l = 100) {
		if($this->_mode == 0) {
			if(!is_null($pos)) {
				fseek($this->file, $pos);
			}
			$this->buffer = $l > 0 ? fread($this->file, $l) : '';
			$this->length = strlen($this->buffer);
			if($this->length < $l)
				$this->increaseLength($l - $this->length);
		} else {
			$this->buffer = $this->file;
			$this->length = strlen($this->buffer);
		}
		$this->offset = 0;
		$this->stack  = array();
	}
	public function ensureContent() {
		if($this->offset >= $this->length - 1) {
			return $this->increaseLength();
		} else {
			return true;
		}
	}
	public function increaseLength($l = 100) {
		if($this->_mode == 0 && feof($this->file)) {
			return false;
		} else if($this->_mode == 0) {
			$totalLength = $this->length + $l;
			do {
				$toRead = $totalLength - $this->length;
				if($toRead < 1)
					break;
				$this->buffer .= fread($this->file, $toRead);
			} while((($this->length = strlen($this->buffer)) != $totalLength) && !feof($this->file));
			return true;
		} else {
			return false;
		}
	}
}
class FilterASCII85 {
	public function decode($in) {
		$ord   = array(
			'~' => ord('~'),
			'z' => ord('z'),
			'u' => ord('u'),
			'!' => ord('!')
		);
		$out   = '';
		$state = 0;
		$chn   = null;
		$l     = strlen($in);
		for($k = 0; $k < $l; ++$k) {
			$ch = ord($in[$k]) & 0xff;
			if($ch == $ord['~']) {
				break;
			}
			if(preg_match('/^\s$/', chr($ch))) {
				continue;
			}
			if($ch == $ord['z'] && $state == 0) {
				$out .= chr(0) . chr(0) . chr(0) . chr(0);
				continue;
			}
			if($ch < $ord['!'] || $ch > $ord['u']) {
				throw new Exception('Illegal character in ASCII85Decode.');
			}
			$chn[$state++] = $ch - $ord['!'];
			if($state == 5) {
				$state = 0;
				$r     = 0;
				for($j = 0; $j < 5; ++$j)
					$r = $r * 85 + $chn[$j];
				$out .= chr($r >> 24);
				$out .= chr($r >> 16);
				$out .= chr($r >> 8);
				$out .= chr($r);
			}
		}
		$r = 0;
		if($state == 1) {
			throw new Exception('Illegal length in ASCII85Decode.');
		}
		if($state == 2) {
			$r = $chn[0] * 85 * 85 * 85 * 85 + ($chn[1] + 1) * 85 * 85 * 85;
			$out .= chr($r >> 24);
		} else if($state == 3) {
			$r = $chn[0] * 85 * 85 * 85 * 85 + $chn[1] * 85 * 85 * 85 + ($chn[2] + 1) * 85 * 85;
			$out .= chr($r >> 24);
			$out .= chr($r >> 16);
		} else if($state == 4) {
			$r = $chn[0] * 85 * 85 * 85 * 85 + $chn[1] * 85 * 85 * 85 + $chn[2] * 85 * 85 + ($chn[3] + 1) * 85;
			$out .= chr($r >> 24);
			$out .= chr($r >> 16);
			$out .= chr($r >> 8);
		}
		return $out;
	}
	public function encode($in) {
		throw new LogicException("ASCII85 encoding not implemented.");
	}
}
class FilterASCIIHexDecode {
	public function decode($data) {
		$data = preg_replace('/[^0-9A-Fa-f]/', '', rtrim($data, '>'));
		if((strlen($data) % 2) == 1) {
			$data .= '0';
		}
		return pack('H*', $data);
	}
	public function encode($data, $leaveEOD = false) {
		return current(unpack('H*', $data)) . ($leaveEOD ? '' : '>');
	}
}
class FilterLZW     {
	protected $_sTable = array();
	protected $_data = null;
	protected $_dataLength = 0;
	protected $_tIdx;
	protected $_bitsToGet = 9;
	protected $_bytePointer;
	protected $_bitPointer;
	protected $_nextData = 0;
	protected $_nextBits = 0;
	protected $_andTable = array(511, 1023, 2047, 4095);
	public function decode($data) {
		if($data[0] == 0x00 && $data[1] == 0x01) {
			throw new Exception('LZW flavour not supported.');
		}
		$this->_initsTable();
		$this->_data        = $data;
		$this->_dataLength  = strlen($data);
		$this->_bytePointer = 0;
		$this->_bitPointer  = 0;
		$this->_nextData    = 0;
		$this->_nextBits    = 0;
		$oldCode            = 0;
		$unCompData         = '';
		while(($code = $this->_getNextCode()) != 257) {
			if($code == 256) {
				$this->_initsTable();
				$code = $this->_getNextCode();
				if($code == 257) {
					break;
				}
				if(!isset($this->_sTable[$code])) {
					throw new Exception('Error while decompression LZW compressed data.');
				}
				$unCompData .= $this->_sTable[$code];
				$oldCode = $code;
			} else {
				if($code < $this->_tIdx) {
					$string = $this->_sTable[$code];
					$unCompData .= $string;
					$this->_addStringToTable($this->_sTable[$oldCode], $string[0]);
					$oldCode = $code;
				} else {
					$string = $this->_sTable[$oldCode];
					$string = $string . $string[0];
					$unCompData .= $string;
					$this->_addStringToTable($string);
					$oldCode = $code;
				}
			}
		}
		return $unCompData;
	}
	protected function _initsTable() {
		$this->_sTable = array();
		for($i = 0; $i < 256; $i++)
			$this->_sTable[$i] = chr($i);
		$this->_tIdx      = 258;
		$this->_bitsToGet = 9;
	}
	protected function _addStringToTable($oldString, $newString = '') {
		$string                        = $oldString . $newString;
		$this->_sTable[$this->_tIdx++] = $string;
		if($this->_tIdx == 511) {
			$this->_bitsToGet = 10;
		} else if($this->_tIdx == 1023) {
			$this->_bitsToGet = 11;
		} else if($this->_tIdx == 2047) {
			$this->_bitsToGet = 12;
		}
	}
	protected function _getNextCode() {
		if($this->_bytePointer == $this->_dataLength) {
			return 257;
		}
		$this->_nextData = ($this->_nextData << 8) | (ord($this->_data[$this->_bytePointer++]) & 0xff);
		$this->_nextBits += 8;
		if($this->_nextBits < $this->_bitsToGet) {
			$this->_nextData = ($this->_nextData << 8) | (ord($this->_data[$this->_bytePointer++]) & 0xff);
			$this->_nextBits += 8;
		}
		$code = ($this->_nextData >> ($this->_nextBits - $this->_bitsToGet)) & $this->_andTable[$this->_bitsToGet - 9];
		$this->_nextBits -= $this->_bitsToGet;
		return $code;
	}
	public function encode($in) {
		throw new LogicException("LZW encoding not implemented.");
	}
}


class dPdfMaker       extends PDF_HTML {
	Function __construct($settings=Array()){
		$settings += Array(
			'paperSize'       =>'A4',
			'paperOrientation'=>'P',
			'defaultEncoding' =>'UTF-8',
			'autoPageBreak'   =>false,
			'fontPath'        =>dirname(__FILE__).'/dPdfMakerFonts/',
		);
		
		parent::__construct($settings['paperOrientation'], 'mm', $settings['paperSize']);
		$this->fontpath = $settings['fontPath'];
		$this->SetAutoPageBreak($settings['autoPageBreak']);
		$this->cMargin  = 0;
		
	
		# $pdf->SetAutoPageBreak(false);
		# $pdf->SetTextColor(0,0,0);
		
	}
	Function setMapPos   (&$mapPosition){
		// Expected:
		// --> Requried: top, left, width[, height]
		// --> Optional: line-height, allow-html, allow-line-break, must-fit
		// --> Optional: text-align,  vertical-align
		$this->mapPos = &$mapPosition;
	}
	
	// Poskey: Array(top, left, width, height)
	Function dWrite($posKey, $string, $settings=Array()){
		if(!is_array($posKey)){
			$posKey = $this->mapPos[$posKey];
		}
		if(isset($posKey[0])){
			$posKey = Array(
				'left'  =>$posKey[0],
				'top'   =>$posKey[1],
				'width' =>$posKey[2],
				'height'=>isset($posKey[3])?$posKey[3]:false,
			);
		}
		
		$string = str_replace("–", "-", $string);
		
		$maxW      = $posKey['width'] ;
		$maxH      = $posKey['height'];
		if($maxW < 0){
			$maxW = $this->w + $maxW - $posKey['left'];
		}
		if($maxH < 0){
			$maxH = $this->h + $maxH - $posKey['top'];
		}
		
		$settings += $posKey;
		$settings += Array(
			'line-height'    =>1.15, // Or '2mm', Or '5lines' (5 Lines in the box given)
			'allow-html'     =>true,
			'auto-line-break'=>true,
			'text-align'     =>'left',
			'vertical-align' =>'top',
			'must-fit'       =>false,
			'border'         =>false,
			'dry-run'        =>false,
			'html-output'    =>false,
		);
		
		if($settings['must-fit']){
			if($settings['must-fit'] === true){
				$settings['must-fit'] = Array(
					'minFontSize'  =>   6,
					'stepFontSize' => 0.25,
				);
			}
			
			$oFontSize     = 
			$curFontSize   = $this->FontSizePt;
			do{
				$gotResult   = $this->dWrite($posKey, $string, Array(
					'dry-run' =>true,
					'must-fit'=>false,
				) + $settings);
				
				if($gotResult['curWidth'] <= $maxW && (!$maxH || $gotResult['curHeight'] <= $maxH)){
					if($settings['html-output']){
						echo "* must-fit: Will fit!<br />";
					}
					break;
				}
				
				if($settings['html-output']){
					echo "* must-fit: Font-size {$curFontSize}pt would give width {$gotResult['curWidth']}, wich is more than maxW={$maxW}. Trying to reduce {$settings['must-fit']['stepFontSize']}pt.<br />";
				}
				
				$curFontSize -= $settings['must-fit']['stepFontSize'];
				if($curFontSize < $settings['must-fit']['minFontSize']){
					// Can't go smaller than this.
					break;
				}
				
				$this->SetFont($this->FontFamily, $this->FontStyle, $curFontSize);
			}
			while(true);
			
			$ret = $this->dWrite($posKey, $string, Array('must-fit'=>false) + $settings) + Array('fontSize'=>$curFontSize);
			$this->SetFont($this->FontFamily, '', $oFontSize);
			return $ret;
		}
		
		$string    = utf8_decode($string);
		$string    = str_replace("\r", "", $string);
		
		$_getLineHeight = function($fontSize) use ($posKey, $settings){
			// * Font size should be ->FontSize, NOT ->FontSizePt
			if(substr($settings['line-height'], -2) == 'mm'){
				return floatval($settings['line-height']);
			}
			elseif(substr($settings['line-height'], -5) == 'lines'){
				return ($posKey['height'] / intval($settings['line-height']));
			}
			
			return $fontSize * $settings['line-height'];
		};
		
		// 1. Separate into chunks
		if($settings['allow-html']){
			$chunkData = Array(
				'font'     =>$this->FontFamily,
				'size'     =>$this->FontSizePt,
				'bold'     =>false,
				'italic'   =>false,
				'underline'=>false,
				'string'   =>'',
			);
			
			$_buffer     = '';
			$writeChunks = Array();
			$_flush      = function() use (&$_buffer, &$writeChunks, &$chunkData){
				$chunk           = $chunkData;
				$chunk['string'] = $_buffer;
				$writeChunks[]   = $chunk;
				$_buffer         = '';
				
				# $writeChunks[]       = $chunkData;
				# $chunkData['string'] = $_buffer;
			};
			for($i = 0; $i < strlen($string); $i++){
				if(substr($string, $i, 3) == "<b>"){
					$_flush();
					$chunkData['bold'] = true; $i += 2;
					continue;
				}
				if(substr($string, $i, 3) == "<i>"){
					$_flush();
					$chunkData['italic'] = true; $i += 2;
					continue;
				}
				if(substr($string, $i, 3) == "<u>"){
					$_flush();
					$chunkData['underline'] = true; $i += 2;
					continue;
				}
				if(substr($string, $i, 4) == "</b>"){
					$_flush();
					$chunkData['bold'] = false; $i += 3;
					continue;
				}
				if(substr($string, $i, 4) == "</i>"){
					$_flush();
					$chunkData['italic'] = false; $i += 3;
					continue;
				}
				if(substr($string, $i, 4) == "</u>"){
					$_flush();
					$chunkData['underline'] = false; $i += 3;
					continue;
				}
				
				# echo "| Logging string[{$i}] ({$string[$i]}). _Buffer={$_buffer}\n";
				$_buffer .= $string[$i];
			}
			$_flush();
		}
		else{
			$writeChunks = Array(
				Array(
					'font'     =>$this->FontFamily,
					'size'     =>$this->FontSizePt,
					'bold'     =>false,
					'italic'   =>false,
					'underline'=>false,
					'string'   =>$string
				)
			);
		}
		
		if($settings['text-align'] == 'justify'){
			$_newChunks = Array();
			foreach($writeChunks as $idx=>$chunk){
				$_allWords = explode(" ", preg_replace("/ +/", " ", $chunk['string']));
				$_sofWords = sizeof($_allWords);
				foreach($_allWords as $idx2=>$word){
					$chunk['string'] = $word.(($_sofWords==$idx2+1)?'':' ');
					$_newChunks[]    = $chunk;
				}
			}
			$writeChunks = $_newChunks;
		}
		
		// 2. Set width and height
		$defChunk = Array(
			'font'     =>false,
			'size'     =>false,
			'bold'     =>false,
			'italic'   =>false,
			'underline'=>false,
			'width'    =>false,
			'height'   =>false,
			'posX'     =>false,
			'posY'     =>false,
			'_userEOL' =>false,
		);
		
		// Split lines
		$curPosX  = 0;
		$curLine  = 0;
		# echo "<pre>";
		for($i=0; $i < sizeof($writeChunks); $i++){
			$chunk  = &$writeChunks[$i];
			$chunk += $defChunk;
			# echo "Chunk: ".var_export($chunk['string'], true)."\n";
			if(!strlen($chunk['string'])){
				# echo "| Chunk vazio, não tenho o que fazer com isso.\n";
				continue;
			}
			
			$this->SetFont($chunk['font'], ($chunk['bold']?'B':'').($chunk['italic']?'I':''), $chunk['size']);
			$chunk['posX']   = $curPosX;
			$chunk['posY']   = $curLine;
			$chunk['height'] = $_getLineHeight($this->FontSize);
			
			if(strpos($chunk['string'], "\n") !== false){
				# echo "| {$i}: Tenho quebra de linha que precisa ser separada antes de continuarmos.\n";
				$_tmpLines = explode("\n", $chunk['string'], 2);
				if(strlen(rtrim($_tmpLines[1], "\n"))){
					$newChunk           = array_merge(array(), $chunk); // Clone removing references
					$chunk['string']    = $_tmpLines[0]."\n";
					$newChunk['string'] = $_tmpLines[1];
					$chunk['_userEOL']  = true;
					# echo "|     Definido _userEOL=TRUE\n";
					
					$writeChunks = array_merge(
						array_slice($writeChunks, 0, $i+1),
						Array($newChunk),
						array_slice($writeChunks, $i+1)
					);
				}
			}
			
			$chunk['width']  = $this->GetStringWidth(rtrim($chunk['string'], "\n"));
			if($curPosX + $chunk['width'] > $maxW && $settings['auto-line-break']){
				# echo "| {$i}: A largura deste chunk terminará fora do limite. curPosX={$curPosX} + chunkW={$chunk['width']} > maxW={$maxW}\n";
				
				$allWords = explode(" ", rtrim($chunk['string'], "\n"));
				$splitIdx = false;
				for($idx = 0; $idx < sizeof($allWords); $idx++){
					$_tmpStr = implode(" ", array_slice($allWords, 0, $idx+1));
					$_tmpW   = $this->GetStringWidth($_tmpStr);
					
					# echo "- Checando largura de '{$_tmpStr}'... {$_tmpW}\r\n";
					if($curPosX + $_tmpW <= $maxW){
						$splitIdx = strlen($_tmpStr);
						continue;
					}
					break;
				}
				if($splitIdx === false){
					if($curPosX > 0){
						# echo "- Nenhuma palavra deste chunk cabe na linha atual.\n";
						# echo "- Vamos encerrar a linha atual e mover o chunk para a linha de baixo.\n";
						$curLine++;
						$curPosX = 0;
						$i--;
						continue;
					}
				}
				if($splitIdx){
					# echo "--&gt; Vou realizar o split na posição {$splitIdx}\n";
					
					$newChunk           = array_merge(array(), $chunk); // Clone removing references
					$newChunk['string'] = substr($chunk['string'], $splitIdx+1);
					$chunk['string']    = substr($chunk['string'], 0, $splitIdx)."\n";
					$chunk['_userEOL']  = false;
					# echo "|     newChunk clonado de chunk. _userEOL=".($chunk['_userEOL']?'true':'false')."\n";
					
					if($newChunk['string'] !== false){
						$writeChunks = array_merge(
							array_slice($writeChunks, 0, $i+1),
							Array($newChunk),
							array_slice($writeChunks, $i+1)
						);
						$chunk['width']  = $this->GetStringWidth(rtrim($chunk['string'], "\n"));
					}
				}
			}
			
			$nEols           = strlen($chunk['string']);
			$chunk['string'] = rtrim($chunk['string'], "\n");
			$nEols          -= strlen($chunk['string']);
			
			if($nEols){
				$curLine += $nEols;
				$curPosX = 0;
			}
			else{
				$curPosX += $chunk['width'];
			}
		}
		unset($chunk); // Remove reference.
		
		// Put into lines and apply text-align and vertical-align
		$allLines = Array();
		foreach($writeChunks as $idx =>$chunk){
			$line          = $chunk['posY'];
			if(!isset($allLines[$line])){
				$allLines[$line] = Array('chunks'=>Array(), 'width'=>0, 'height'=>0);
			}
			
			$writeChunks[$idx]['posY']  *= $chunk['height'];
			$allLines[$line]['chunks'][] = &$writeChunks[$idx];
			$allLines[$line]['width']   += $chunk['width'];
			$allLines[$line]['height']   = ($chunk['height']>$allLines[$line]['height'])?
				$chunk['height']:
				$allLines[$line]['height'];
		}
		
		$curWidth  = max      (array_map(function($item){ return $item['width']; },  $allLines));
		$curHeight = array_sum(array_map(function($item){ return $item['height']; }, $allLines));
		
		if($settings['text-align']     != 'left'){
			foreach($allLines as $line=>$lineItem){
				if($settings['text-align'] == 'center' || $settings['text-align'] == 'right'){
					$moveX = ($settings['text-align'] == 'center')?
						($maxW-$lineItem['width'])/2: // Center
						($maxW-$lineItem['width']);   // Right
					
					foreach($lineItem['chunks'] as $chunkIdx=>$chunk){
						$allLines[$line]['chunks'][$chunkIdx]['posX'] += $moveX;
					}
				}
				if($settings['text-align'] == 'justify'){
					if(($line+1)==sizeof($allLines)){
						// Is last line, don't justify there.
						continue;
					}
					if(!$lineItem['width']){
						// Line placeholder, don't justify it.
						continue;
					}
					
					$lastChunkIdx = end(array_keys($lineItem['chunks']));
					$lastChunk    = $lineItem['chunks'][$lastChunkIdx];
					if($lastChunk['_userEOL']){
						// User-defined line-break, don't justify it.
						continue;
					}
					
					$moveX = ($maxW-$lineItem['width'])/(sizeof($lineItem['chunks'])-1);
					# echo "Justificando linha {$line}.\n";
					# echo "| MoveX={$moveX}\n";
					
					foreach($lineItem['chunks'] as $chunkIdx=>$chunk){
						$allLines[$line]['chunks'][$chunkIdx]['posX']  += $moveX*$chunkIdx;
						if($chunkIdx != $lastChunkIdx){
							// Solve underline problems.
							$allLines[$line]['chunks'][$chunkIdx]['width'] += $moveX;
						}
					}
				}
			}
		}
		# echo "</pre>";
		if($settings['vertical-align'] != 'top' && $maxH){
			# echo "--&gt; Current height: {$curHeight}\n";
			$moveY     = ($settings['vertical-align'] == 'bottom')?
				($maxH-$curHeight):
				($maxH-$curHeight)/2;
			
			foreach($writeChunks as $idx=>$chunk){
				$writeChunks[$idx]['posY'] += $moveY;
			}
		}
		if($settings['html-output']){
			echo "<div style='position: absolute; right: 100px'>";
			echo "	<div style='position: relative; background: #EEE; width: 100mm; height: 80mm; margin: 15px'>";
			foreach($writeChunks as $chunk){
				echo "<span style='font-size: {$chunk['size']}pt; white-space: nowrap; font-family: {$chunk['font']}; position: absolute; left: {$chunk['posX']}mm; top: {$chunk['posY']}mm; height: {$chunk['height']}mm; width: {$chunk['width']}mm; border: 1px solid #888; margin: -1px'>";
				echo $chunk['bold']?"<b>":"";
				echo $chunk['italic']?"<i>":"";
				echo $chunk['underline']?"<u>":"";
				echo utf8_encode($chunk['string']);
				echo $chunk['underline']?"</u>":"";
				echo $chunk['italic']?"</i>":"";
				echo $chunk['bold']?"</b>":"";
				echo "</span>";
			}
			echo "	</div>";
			echo "</div>";
			
			echo "<table cellpadding='3' style='font: 12px Arial'>";
			echo "	<tr bgcolor='#CCFFCC'>";
			echo "		<td>idx</td>";
			echo "		<td>posX</td>";
			echo "		<td>posY</td>";
			echo "		<td>width</td>";
			echo "		<td>height</td>";
			echo "		<td>style</td>";
			echo "		<td>_userEOL</td>";
			echo "		<td>string</td>";
			echo "	</tr>";
			foreach($writeChunks as $chunkIdx=>$chunk){
				echo "<tr bgcolor='".(($chunkIdx%2)?'#EEEEEE':'#DDDDDD')."'>";
				echo "	<td align='center'>{$chunkIdx}</td>";
				echo "	<td align='center' title='{$chunk['posX']}'>".number_format($chunk['posX'], 2)."</td>";
				echo "	<td align='center' title='{$chunk['posY']}'>".number_format($chunk['posY'], 2)."</td>";
				echo "	<td align='center' title='{$chunk['width']}'>".number_format($chunk['width'], 2)."</td>";
				echo "	<td align='center' title='{$chunk['height']}'>".number_format($chunk['height'], 2)."</td>";
				echo "	<td align='center'>";
				echo ($chunk['bold']?'B':'').($chunk['italic']?'I':'').($chunk['underline']?'U':'');
				echo "	</td>";
				echo "	<td align='center'>".($chunk['_userEOL']?'true':'false')."</td>";
				echo "	<td><pre style='font-size: 10px'>'".htmlspecialchars(utf8_encode(str_replace("\n", "[BARRAN]", $chunk['string'])))."'</pre></td>";
				echo "</tr>";
			}
			echo "</table>";
		}
		if(!$settings['dry-run']){
			$this->SetXY    ($posKey['left'], $posKey['top']);
			if($settings['border']){
				$this->MultiCell($maxW, $maxH?$maxH:$_getLineHeight($this->FontSize), "", "1");
			}
			foreach($writeChunks as $chunk){
				if(!strlen($chunk['string']))
					continue;
				
				$this->SetFont($chunk['font'],  ($chunk['bold']?'B':'').($chunk['italic']?'I':''), $chunk['size']);
				$this->SetXY  ($posKey['left']+$chunk['posX'], $posKey['top']+$chunk['posY']);
				$this->Cell   ($chunk['width'], $chunk['height'], $chunk['string'], ($settings['border']?'1':($chunk['underline']?'B':'')));
			}
		}
		
		return Array(
			'curWidth' =>$curWidth,
			'curHeight'=>$curHeight,
			'maxW'     =>$maxW,
			'maxH'     =>$maxH,
			'nLines'   =>sizeof($allLines),
			'allLines' =>$allLines,
		);
	}
	
	Function writeFromPdfMaker($jsonFormat, $data, $showBorder=false){
		if(is_string($jsonFormat)){
			$jsonFormat = json_decode($_POST['jsondata'], true);
		}
		
		$out = Array();
		foreach($jsonFormat as $item){
			$tmp = Array();
			$tmp['mapPos'] = Array($item['left'], $item['top'], $item['width'], $item['height']);
			$tmp['params'] = Array();
			$tmp['params']['line-height']     = "'{$item['limitValue']}lines'";
			$tmp['params']['line-height']     = $item['lineHeight'];
			$tmp['params']['allow-html']      = ($item['fontStyle']=='html');
			$tmp['params']['auto-line-break'] = ($item['overflow'] =='line-break');
			$tmp['params']['text-align']      = $item['align'];
			$tmp['params']['vertical-align']  = $item['valign'];
			$tmp['params']['must-fit']        = ($item['overflow'] =='must-fit'  );
			$tmp['params']['border']          = $showBorder;
			if($item['limitBy'] == ''){
				unset($tmp['mapPos'][3]);
			}
			if(isset($out[$item['name']])){
				$n = 1;
				do{
					$tryName = $item['name'].$n++;
				} while(isset($out[$tryName]));
				$item['name'] = $tryName;
			}
			
			$out[$item['name']] = $tmp;
		}
		
		$mapPos = Array();
		foreach($out as $name=>$props){
			$mapPos[$name] = $props['mapPos'];
		}
		$this->setMapPos($mapPos);
		
		foreach($out as $name=>$props){
			$this->dWrite($name, $data[$name], $props['params']);
		}
	}
}
