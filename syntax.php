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
        
        $params = [
            'fields' => [],
            'images' => [],
            'sections' => [],
            'collapsed_sections' => []
        ];
        
        $currentSection = null;
        $imageIndex = 0;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Check for section headers (lines starting with ==)
            if (preg_match('/^(={2,3})\s*(.+?)\s*\1$/', $line, $matches)) {
                $currentSection = $matches[2];
                $params['sections'][$currentSection] = [];
                // Three equals means collapsed by default
                if ($matches[1] === '===') {
                    $params['collapsed_sections'][$currentSection] = true;
                }
                continue;
            }
            
            // Check for image definitions (image=, image1=, image2=, etc.)
            if (preg_match('/^image(\d*)\s*=\s*(.+)$/', $line, $matches)) {
                $imgNum = $matches[1] ?: '1';
                $imgData = trim($matches[2]);
                
                // Check if image has a caption (format: filename|caption)
                if (strpos($imgData, '|') !== false) {
                    list($imgPath, $caption) = explode('|', $imgData, 2);
                    $params['images'][$imgNum] = [
                        'path' => trim($imgPath),
                        'caption' => trim($caption)
                    ];
                } else {
                    $params['images'][$imgNum] = [
                        'path' => $imgData,
                        'caption' => ''
                    ];
                }
                continue;
            }
            
            // Regular key=value pairs
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Skip empty values unless explicitly showing them
                if (empty($value) && $value !== '0') {
                    continue;
                }
                
                // Special handling for image field without number
                if ($key === 'image') {
                    // Check if image has a caption (format: filename|caption)
                    if (strpos($value, '|') !== false) {
                        list($imgPath, $caption) = explode('|', $value, 2);
                        $params['images']['1'] = [
                            'path' => trim($imgPath),
                            'caption' => trim($caption)
                        ];
                    } else {
                        $params['images']['1'] = [
                            'path' => $value,
                            'caption' => ''
                        ];
                    }
                } elseif ($key === 'name' || $key === 'title') {
                    $params['name'] = $value;
                } elseif ($key === 'header_image') {
                    $params['header_image'] = $value;
                } else {
                    // Add to current section or main fields
                    if ($currentSection !== null) {
                        $params['sections'][$currentSection][$key] = $value;
                    } else {
                        $params['fields'][$key] = $value;
                    }
                }
            }
        }
        
        return $params;
    }

    public function render($mode, Doku_Renderer $renderer, $data) {
        if ($mode != 'xhtml') return false;
        
        // Generate unique ID for this infobox
        $boxId = 'infobox_' . md5(serialize($data));
        
        // Allow custom CSS classes
        $customClass = isset($data['fields']['class']) ? ' ' . hsc($data['fields']['class']) : '';
        unset($data['fields']['class']);
        
        $renderer->doc .= '<div class="infobox' . $customClass . '" id="' . $boxId . '" role="complementary" aria-label="Information box">';
        
        // Header image (optional)
        if (isset($data['header_image'])) {
            $renderer->doc .= '<div class="infobox-header-image">';
            $renderer->internalmedia(
                $data['header_image'],
                null,
                null,
                null,
                null,
                'cache',
                'details'
            );
            $renderer->doc .= '</div>';
        }
        
        // Title
        if (isset($data['name'])) {
            $renderer->doc .= '<div class="infobox-title">' . $this->_parseWikiText($data['name']) . '</div>';
        }
        
        // Multiple images with tabs
        if (!empty($data['images'])) {
            $renderer->doc .= '<div class="infobox-images">';
            
            // Image tabs
            if (count($data['images']) > 1) {
                $renderer->doc .= '<div class="infobox-image-tabs" role="tablist">';
                $first = true;
                $tabCount = 1;
                foreach ($data['images'] as $num => $imgData) {
                    $tabLabel = $imgData['caption'] ?: 'Image ' . $tabCount;
                    $activeClass = $first ? ' active' : '';
                    $tabIndex = $first ? '0' : '-1';
                    $ariaSelected = $first ? 'true' : 'false';
                    $renderer->doc .= '<button class="infobox-tab' . $activeClass . '" role="tab" aria-selected="' . $ariaSelected . '" onclick="showInfoboxImage(\'' . $boxId . '\', ' . $num . ')" aria-label="View ' . hsc($tabLabel) . '" tabindex="' . $tabIndex . '">';
                    // Add tab number if no custom caption provided
                    if (!$imgData['caption'] && count($data['images']) > 1) {
                        $renderer->doc .= '<span class="infobox-tab-number">' . $tabCount . '. </span>';
                    }
                    $renderer->doc .= hsc($tabLabel);
                    $renderer->doc .= '</button>';
                    $first = false;
                    $tabCount++;
                }
                $renderer->doc .= '</div>';
            }
            
            // Image containers
            $first = true;
            foreach ($data['images'] as $num => $imgData) {
                $activeClass = $first ? ' active' : '';
                $renderer->doc .= '<div class="infobox-image-container' . $activeClass . '" id="' . $boxId . '_img_' . $num . '" role="tabpanel">';
                
                // Use DokuWiki's internal media rendering for proper lightbox support
                $renderer->internalmedia(
                    $imgData['path'],
                    $imgData['caption'] ?: null,
                    null,
                    300,
                    null,
                    'cache',
                    'details'
                );
                
                if ($imgData['caption'] && count($data['images']) == 1) {
                    $renderer->doc .= '<div class="infobox-image-caption">' . hsc($imgData['caption']) . '</div>';
                }
                $renderer->doc .= '</div>';
                $first = false;
            }
            
            $renderer->doc .= '</div>';
        }
        
        // Main fields table
        if (!empty($data['fields'])) {
            $renderer->doc .= '<table class="infobox-table">';
            foreach ($data['fields'] as $key => $value) {
                $renderer->doc .= '<tr>';
                $renderer->doc .= '<th>' . hsc($this->_formatKey($key)) . '</th>';
                $renderer->doc .= '<td>' . $this->_parseWikiText($value) . '</td>';
                $renderer->doc .= '</tr>';
            }
            $renderer->doc .= '</table>';
        }
        
        // Sections
        foreach ($data['sections'] as $sectionName => $sectionFields) {
            $sectionId = $boxId . '_section_' . md5($sectionName);
            $isCollapsed = isset($data['collapsed_sections'][$sectionName]);
            $collapsibleClass = $isCollapsed ? ' collapsible collapsed' : '';
            
            $renderer->doc .= '<div class="infobox-section' . $collapsibleClass . '">';
            if ($isCollapsed) {
                $renderer->doc .= '<div class="infobox-section-header" onclick="toggleInfoboxSection(\'' . $sectionId . '\')" ' . 
                                 'role="button" tabindex="0" aria-expanded="false" aria-controls="' . $sectionId . '">';
            } else {
                $renderer->doc .= '<div class="infobox-section-header">';
            }
            $renderer->doc .= hsc($sectionName);
            if ($isCollapsed) {
                $renderer->doc .= '<span class="infobox-section-toggle">▼</span>';
            }
            $renderer->doc .= '</div>';
            
            $contentClass = $isCollapsed ? 'infobox-section-content collapsed' : 'infobox-section-content';
            $renderer->doc .= '<div class="' . $contentClass . '" id="' . $sectionId . '">';
            
            if (!empty($sectionFields)) {
                $renderer->doc .= '<table class="infobox-table">';
                foreach ($sectionFields as $key => $value) {
                    $renderer->doc .= '<tr>';
                    $renderer->doc .= '<th>' . hsc($this->_formatKey($key)) . '</th>';
                    $renderer->doc .= '<td>' . $this->_parseWikiText($value) . '</td>';
                    $renderer->doc .= '</tr>';
                }
                $renderer->doc .= '</table>';
            }
            $renderer->doc .= '</div>';
            $renderer->doc .= '</div>';
        }
        
        $renderer->doc .= '</div>';
        
        // Add JavaScript for image tabs (only once per page)
        static $jsAdded = false;
        if (!$jsAdded) {
            $renderer->doc .= '<script>
            function showInfoboxImage(boxId, imageNum) {
                // Hide all images in this infobox
                var containers = document.querySelectorAll("#" + boxId + " .infobox-image-container");
                containers.forEach(function(container) {
                    container.classList.remove("active");
                });
                
                // Remove active class from all tabs in this infobox
                var tabs = document.querySelectorAll("#" + boxId + " .infobox-tab");
                tabs.forEach(function(tab) {
                    tab.classList.remove("active");
                    tab.setAttribute("tabindex", "-1");
                    tab.setAttribute("aria-selected", "false");
                });
                
                // Show selected image
                document.getElementById(boxId + "_img_" + imageNum).classList.add("active");
                
                // Add active class to clicked tab
                var activeTab = tabs[imageNum - 1];
                activeTab.classList.add("active");
                activeTab.setAttribute("tabindex", "0");
                activeTab.setAttribute("aria-selected", "true");
            }
            
            function toggleInfoboxSection(sectionId) {
                var content = document.getElementById(sectionId);
                var header = content.previousElementSibling;
                var toggle = header.querySelector(".infobox-section-toggle");
                
                if (content.classList.contains("collapsed")) {
                    content.classList.remove("collapsed");
                    header.setAttribute("aria-expanded", "true");
                    if (toggle) toggle.textContent = "▲";
                } else {
                    content.classList.add("collapsed");
                    header.setAttribute("aria-expanded", "false");
                    if (toggle) toggle.textContent = "▼";
                }
            }
            
            // Add keyboard support
            document.addEventListener("DOMContentLoaded", function() {
                // Keyboard navigation for image tabs
                var tabGroups = document.querySelectorAll(".infobox-image-tabs");
                tabGroups.forEach(function(tabGroup) {
                    var tabs = tabGroup.querySelectorAll(".infobox-tab");
                    
                    tabs.forEach(function(tab, index) {
                        tab.addEventListener("keydown", function(e) {
                            var newIndex = -1;
                            
                            switch(e.key) {
                                case "ArrowLeft":
                                    e.preventDefault();
                                    newIndex = index - 1;
                                    if (newIndex < 0) newIndex = tabs.length - 1;
                                    break;
                                case "ArrowRight":
                                    e.preventDefault();
                                    newIndex = index + 1;
                                    if (newIndex >= tabs.length) newIndex = 0;
                                    break;
                                case "Home":
                                    e.preventDefault();
                                    newIndex = 0;
                                    break;
                                case "End":
                                    e.preventDefault();
                                    newIndex = tabs.length - 1;
                                    break;
                            }
                            
                            if (newIndex >= 0) {
                                tabs[newIndex].focus();
                                tabs[newIndex].click();
                            }
                        });
                    });
                });
                
                // Keyboard support for collapsible sections
                var headers = document.querySelectorAll(".infobox-section.collapsible .infobox-section-header");
                headers.forEach(function(header) {
                    header.addEventListener("keydown", function(e) {
                        if (e.key === "Enter" || e.key === " ") {
                            e.preventDefault();
                            header.click();
                        }
                    });
                });
                
                // Fix for section header lines
                var infoboxes = document.querySelectorAll(".infobox");
                if (infoboxes.length > 0) {
                    var headers = document.querySelectorAll("h1, h2, h3, h4, h5, h6");
                    headers.forEach(function(header) {
                        header.style.overflow = "hidden";
                    });
                    
                    var pageContent = document.querySelector(".dokuwiki .page");
                    if (pageContent && !pageContent.classList.contains("has-infobox")) {
                        pageContent.classList.add("has-infobox");
                    }
                }
            });
            </script>';
            $jsAdded = true;
        }
        
        return true;
    }
    
    private function _parseWikiText($text) {
        // Handle multi-line values properly
        $text = str_replace('\n', "\n", $text);
        
        $info = array();
        $xhtml = p_render('xhtml', p_get_instructions($text), $info);
        
        // Remove wrapping <p> tags if it's a single paragraph
        if (substr_count($xhtml, '<p>') == 1) {
            $xhtml = preg_replace('/^\s*<p>(.*)<\/p>\s*$/s', '$1', $xhtml);
        }
        
        return $xhtml;
    }
    
    private function _formatKey($key) {
        // Convert underscores to spaces and capitalize words
        return ucwords(str_replace('_', ' ', $key));
    }
}