<?php
/**
 * DokuWiki Plugin infobox (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 */
if (!defined('DOKU_INC')) die();

class syntax_plugin_infobox extends DokuWiki_Syntax_Plugin {
    public function getType() {
        return 'substition';
    }

    public function getPType() {
        return 'block';
    }

    public function getSort() {
        return 199;
    }

    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{infobox>.*?\}\}', $mode, 'plugin_infobox');
    }

    public function handle($match, $state, $pos, Doku_Handler $handler) {
        $data = substr($match, 10, -2);
        $lines = explode("\n", $data);
        $params = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $params[trim($key)] = trim($value);
            }
        }
        return $params;
    }

    public function render($mode, Doku_Renderer $renderer, $data) {
        if ($mode != 'xhtml') return false;

        $renderer->doc .= '<div class="infobox">';
        
        if (isset($data['name'])) {
            $renderer->doc .= '<div class="infobox-title">' . $this->_parseWikiText($data['name']) . '</div>';
        }
        
        if (isset($data['image'])) {
            $renderer->doc .= '<div class="infobox-image"><img src="' . ml($data['image']) . '" alt="' . hsc($data['name']) . '"></div>';
        }
        
        $renderer->doc .= '<table class="infobox-table">';
        foreach ($data as $key => $value) {
            if ($key != 'name' && $key != 'image') {
                $renderer->doc .= '<tr><th>' . hsc($key) . '</th><td>' . $this->_parseWikiText($value) . '</td></tr>';
            }
        }
        $renderer->doc .= '</table>';
        
        $renderer->doc .= '</div>';
        return true;
    }

    private function _parseWikiText($text) {
        $info = array();
        $xhtml = p_render('xhtml', p_get_instructions($text), $info);
        return $xhtml;
    }
}
