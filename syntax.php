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
        $this->Lexer->addEntryPattern('\{\{infobox>', $mode, 'plugin_infobox');
    }
    
    public function postConnect() {
        $this->Lexer->addExitPattern('\}\}', 'plugin_infobox');
    }

    public function handle($match, $state, $pos, Doku_Handler $handler) {
        switch ($state) {
            case DOKU_LEXER_ENTER:
                return array('state' => 'enter');
                
            case DOKU_LEXER_UNMATCHED:
                // This contains the actual content between {{infobox> and }}
                $lines = explode("\n", $match);
                
                $params = [
                    'fields' => [],
                    'images' => [],
                    'sections' => [],
                    'collapsed_sections' => []
                ];
                
                $currentSection = null;
                $currentSubgroup = null;
                $imageIndex = 0;
                $currentKey = null;
                $currentValue = '';
                
                foreach ($lines as $line) {
                    // Don't trim the line yet - we need to preserve indentation for multi-line values
                    
                    // Check if we're currently capturing a multi-line value
                    if ($currentKey !== null) {
                        // Continue capturing multi-line value
                        $currentValue .= "\n" . $line;
                        
                        // Check if all plugin syntaxes are closed
                        if (substr_count($currentValue, '{{') === substr_count($currentValue, '}}')) {
                            $this->_saveField($params, $currentKey, trim($currentValue), $currentSection, $currentSubgroup);
                            $currentKey = null;
                            $currentValue = '';
                        }
                        continue;
                    }
                    
                    $trimmedLine = trim($line);
                    if (empty($trimmedLine)) continue;
                    
                    // Check for section headers
                    if (preg_match('/^(={2,3})\s*(.+?)\s*\1$/', $trimmedLine, $sectionMatches)) {
                        $currentSection = $sectionMatches[2];
                        $currentSubgroup = null; // Reset subgroup when entering new section
                        $params['sections'][$currentSection] = [];
                        if ($sectionMatches[1] === '===') {
                            $params['collapsed_sections'][$currentSection] = true;
                        }
                        continue;
                    }
                    
                    // Check for subgroup headers (:::)
                    if (preg_match('/^:::\s*(.+?)\s*:::$/', $trimmedLine, $subgroupMatches)) {
                        if ($currentSection !== null) {
                            $currentSubgroup = $subgroupMatches[1];
                            if (!isset($params['sections'][$currentSection]['_subgroups'])) {
                                $params['sections'][$currentSection]['_subgroups'] = [];
                            }
                            $params['sections'][$currentSection]['_subgroups'][$currentSubgroup] = [];
                        }
                        continue;
                    }
                    
                    // Check if this line contains a key=value pair
                    if (strpos($trimmedLine, '=') !== false) {
                        // Split only on the first = to handle values containing =
                        $pos = strpos($trimmedLine, '=');
                        $key = trim(substr($trimmedLine, 0, $pos));
                        $value = trim(substr($trimmedLine, $pos + 1));
                        
                        // Check if value contains unclosed plugin syntax
                        $openCount = substr_count($value, '{{');
                        $closeCount = substr_count($value, '}}');
                        
                        if ($openCount > $closeCount) {
                            // Value contains unclosed plugin syntax, start multi-line capture
                            $currentKey = $key;
                            $currentValue = $value;
                        } else {
                            // Value is complete on this line
                            $this->_saveField($params, $key, $value, $currentSection, $currentSubgroup);
                        }
                    }
                }
                
                // Save any remaining multi-line value
                if ($currentKey !== null) {
                    $this->_saveField($params, $currentKey, trim($currentValue), $currentSection, $currentSubgroup);
                }
                
                return array('state' => 'content', 'params' => $params);
                
            case DOKU_LEXER_EXIT:
                return array('state' => 'exit');
        }
        
        return false;
    }
    
    private function _saveField(&$params, $key, $value, $currentSection, $currentSubgroup = null) {
        // Skip empty values unless explicitly showing them
        if (empty($value) && $value !== '0') {
            return;
        }
        
        // Handle image fields
        if (preg_match('/^image(\d*)$/', $key, $matches)) {
            $imgNum = $matches[1] ?: '1';
            // Check if image has a caption (format: filename|caption)
            if (strpos($value, '|') !== false) {
                list($imgPath, $caption) = explode('|', $value, 2);
                $params['images'][$imgNum] = [
                    'path' => trim($imgPath),
                    'caption' => trim($caption)
                ];
            } else {
                $params['images'][$imgNum] = [
                    'path' => trim($value),
                    'caption' => ''
                ];
            }
        } elseif ($key === 'name' || $key === 'title') {
            $params['name'] = $value;
        } elseif ($key === 'header_image') {
            $params['header_image'] = $value;
        } elseif ($key === 'class') {
            $params['class'] = $value;
        } else {
            // Add to current section/subgroup or main fields
            if ($currentSection !== null) {
                if ($currentSubgroup !== null) {
                    // Add to subgroup
                    $params['sections'][$currentSection]['_subgroups'][$currentSubgroup][$key] = $value;
                } else {
                    // Add to section (not in a subgroup)
                    $params['sections'][$currentSection][$key] = $value;
                }
            } else {
                // Add to main fields
                $params['fields'][$key] = $value;
            }
        }
    }

    public function render($mode, Doku_Renderer $renderer, $data) {
        if ($mode != 'xhtml') return false;
        
        if (!is_array($data) || !isset($data['state'])) return false;
        
        switch ($data['state']) {
            case 'enter':
                // Start of infobox - nothing to do
                break;
                
            case 'content':
                // Render the actual infobox
                $params = $data['params'];
                $this->_renderInfobox($renderer, $params);
                break;
                
            case 'exit':
                // End of infobox - nothing to do
                break;
        }
        
        return true;
    }
    
    private function _renderInfobox($renderer, $data) {
        // Generate unique ID for this infobox
        $boxId = 'infobox_' . md5(serialize($data));
        
        // Allow custom CSS classes
        $customClass = isset($data['class']) ? ' ' . hsc($data['class']) : '';
        
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
                $renderer->doc .= '<th>' . $this->_renderFieldName($key) . '</th>';
                $renderer->doc .= '<td>' . $this->_parseWikiText($value) . '</td>';
                $renderer->doc .= '</tr>';
            }
            $renderer->doc .= '</table>';
        }
        
        // Sections
        foreach ($data['sections'] as $sectionName => $sectionData) {
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
            
            // Check if this section has subgroups
            $hasSubgroups = isset($sectionData['_subgroups']) && !empty($sectionData['_subgroups']);
            
            if ($hasSubgroups) {
                // Render subgroups
                $renderer->doc .= '<div class="infobox-subgroups">';
                foreach ($sectionData['_subgroups'] as $subgroupName => $subgroupFields) {
                    $renderer->doc .= '<div class="infobox-subgroup">';
                    $renderer->doc .= '<div class="infobox-subgroup-header">' . hsc($subgroupName) . '</div>';
                    
                    if (!empty($subgroupFields)) {
                        $renderer->doc .= '<table class="infobox-table">';
                        foreach ($subgroupFields as $key => $value) {
                            $renderer->doc .= '<tr>';
                            $renderer->doc .= '<th>' . $this->_renderFieldName($key) . '</th>';
                            $renderer->doc .= '<td>' . $this->_parseWikiText($value) . '</td>';
                            $renderer->doc .= '</tr>';
                        }
                        $renderer->doc .= '</table>';
                    }
                    $renderer->doc .= '</div>';
                }
                $renderer->doc .= '</div>';
            }
            
            // Render regular section fields (not in subgroups)
            $regularFields = array_filter($sectionData, function($key) {
                return $key !== '_subgroups';
            }, ARRAY_FILTER_USE_KEY);
            
            if (!empty($regularFields)) {
                $renderer->doc .= '<table class="infobox-table">';
                foreach ($regularFields as $key => $value) {
                    $renderer->doc .= '<tr>';
                    $renderer->doc .= '<th>' . $this->_renderFieldName($key) . '</th>';
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
    
    private function _renderFieldName($key) {
        // Handle pipe syntax for icons: "icon.png|Field Name"
        if (strpos($key, '|') !== false && preg_match('/^([^|]+)\|(.+)$/', $key, $matches)) {
            $iconFile = trim($matches[1]);
            $label = trim($matches[2]);
            
            // Check if the first part looks like an image file
            if (preg_match('/\.(png|jpg|jpeg|gif|svg)$/i', $iconFile)) {
                // Use DokuWiki's media resolution instead of manual path construction
                global $conf;
                
                // Try to resolve the media file using DokuWiki's functions
                $mediaFile = cleanID($iconFile);
                $file = mediaFN($mediaFile);
                
                if (file_exists($file)) {
                    // File exists, use DokuWiki's media URL
                    $iconHtml = '<img src="' . ml($mediaFile) . '" alt="" class="infobox-field-icon" />';
                } else {
                    // Fallback: try as direct media reference with debugging
                    $iconHtml = '<img src="' . DOKU_URL . 'data/media/' . hsc($iconFile) . '" alt="[' . hsc($iconFile) . ']" class="infobox-field-icon" title="Icon: ' . hsc($iconFile) . '" />';
                }
                
                return $iconHtml . hsc($this->_formatKey($label));
            }
        }
        
        // No icons, just format normally
        return hsc($this->_formatKey($key));
    }
}