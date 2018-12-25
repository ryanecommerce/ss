<?php if ( ! defined('WP_CONTENT_DIR')) exit('No direct script access allowed'); ?>
<?php


class RDP_WIKI_EMBED_CONTENT {
    private $_hasError = false;
    private $_lastMessage = '';
    private $_key = '';
    private $_contentRaw = '';
    private $_content = '';
    private $_dateCreated = null;
    private $_rootSource = '';
    
    public function __construct($props = null){
        if(!$props){
            $this->_hasError = true;
            $this->_lastMessage = esc_html__('Missing props parameter.','rdp-wiki-embed');
            return ;        
        }
        
       foreach ($props as $key => $value ) {
           $newvalue = (isset($props[$key])) ? $props[$key] : null;
           if ($newvalue === "true") $newvalue = true;
           if ($newvalue === "false") $newvalue = false;
           $this->$key = $newvalue;
       }
       
       $this->_dateCreated = current_time( 'mysql' );
       $this->_key = RDP_WIKI_EMBED_UTILITIES::getKey($this->url);
       
        $oURLPieces = parse_url($this->url);  
        if(key_exists('path', $oURLPieces)) unset($oURLPieces['path']);
        if(key_exists('port', $oURLPieces)) unset($oURLPieces['port']);
        if(key_exists('user', $oURLPieces)) unset($oURLPieces['user']);
        if(key_exists('pass', $oURLPieces)) unset($oURLPieces['pass']);
        if(key_exists('query', $oURLPieces)) unset($oURLPieces['query']); 
        if(key_exists('fragment', $oURLPieces)) unset($oURLPieces['fragment']);       
        $this->_rootSource = RDP_WIKI_EMBED_UTILITIES::unparse_url($oURLPieces); 
       
    }//__construct 
    
    public function hasError(){
        return $this->_hasError;
    }
    
    public function lastMessage(){
        return $this->_lastMessage;
    } 
    
    public function fetch() {
        global $wpdb;
        $wpdb->suppress_errors();
        $wpdb->show_errors(false);        
        $table = RDP_WIKI_EMBED_TABLE;
        $sSQL = sprintf('Select wiki_content, date_created From %s Where wiki_key = "%s";',  $table, $this->_key);
        $row = $wpdb->get_row($sSQL);
        if($row){
            $this->_contentRaw = $row->wiki_content;
            $this->_dateCreated = $row->date_created;
        }
        
        $fIsExpired = $this->isExpired();
        if(empty($this->_contentRaw) || $fIsExpired){
            $this->content_get($wpdb,$table);
        }
        
        $this->preRender();
        
        return $this->_content;
    }//fetch
    
    private function preRender() {
        if($this->_hasError){
            $label = __('ERROR', 'rdp-wiki-embed');
            $msg = sprintf('<span></span> %s: %s', $label, $this->_lastMessage);
            $this->_content = RDP_WIKI_EMBED_UTILITIES::showMessage($msg, true, false);
            return;
        }
        
        $permalink = get_the_permalink();
        $html = new rdp_simple_html_dom();
        $html->load('<html>'.$this->_contentRaw.'</html>',true,false);
        
        $remove_elements = explode( ",", $this->remove );
        if(empty($this->_options['edit_show'])){
            array_push($remove_elements,'.editsection');
            array_push($remove_elements,'.mw-editsection');
        }
        if(empty($this->_options['toc_show'])) array_push($remove_elements, '#toc'); 
        if(empty($this->_options['infobox_show'])) array_push($remove_elements, '.infobox'); 
        if(empty($this->_options['admin_nav_show'])) array_push($remove_elements, '#mw-navigation'); 
        if(empty($this->_options['footer_show'])) array_push($remove_elements, '#footer'); 
        
        
        $remove_elements = apply_filters('rdp_we_prerender_remove_elements_filter', $remove_elements);
        
        foreach ( $remove_elements as $element ) {
            foreach($html->find($element) as $e) 
            {
                $e->outertext = '';
            }            
        }

        if(empty($this->_options['unreferenced_show'])){
            foreach($html->find('table[class*=ambox-Unreferenced') as $e) 
            {
                $e->outertext = '';
            }            
        }
        
        $fOpenNew = ($this->_options['wiki_links'] == 'default' && !empty($this->_options['wiki_links_open_new']));
        $fOverwrite = ($this->_options['wiki_links'] === 'overwrite');
        
        $source = $html->find('.printfooter',0);
        if($source){
            if(!$this->_options['source_show']){
                $source->outertext = '';
            }else{
                $source->innertext = sprintf('%s <a rel="external_link" class="external" href="%s">%s</a>', $this->_options['pre_source'], $this->url, $this->url);
            }            
        }        

         
        $eFileTOC = $html->find('#filetoc',0);
        if($eFileTOC){
            $this->_content = $html->find('body',0)->innertext; 
            return;
        }

        $len = strlen($this->_rootSource);

        foreach($html->find('a') as $link){
            $fIsExternal = false;
            $fIsFile = false;
            $pos = -1;
            $classes = array();

            if(isset($link->class)){
                $classes = explode(' ',$link->class) ;
            }              

            if(in_array('external', $classes)){
                $fIsExternal = true;
            }

            if(isset($link->href)){
                if(!$fIsExternal):
                    $pos = (substr(strtolower($link->href), 0, $len) === $this->_rootSource);
                    $fIsExternal = !($pos === true);                   
                endif;

                if($fIsExternal)$link->rel = 'external_link'; 

                $pos = true;
                if(substr($link->href, 0, 1) !== '#'){
                    $pos = false;  
                    $fIsFile = RDP_WIKI_EMBED_UTILITIES::isScriptStyleImgRequest($link->href);                  
                }                    
            }             

            if(!$fOverwrite && 
                $fOpenNew && 
                isset($link->href) &&
                ($pos === false)){
                    $link->target = '_new';                    
            }

            if(!$fIsExternal && 
                isset($link->href) &&
                ($pos === false)){
                if(isset($link->class)){
                    $link->class .= ' wiki-link';
                }else{
                    $link->class = 'wiki-link';
                }
            }            

            if( !$fIsExternal && 
                $fOverwrite && 
                isset($link->href) &&
                ($pos === false)){
                    $sHREF = $link->href;
                    $encodedURL = urlencode($link->href);
                    // restore hashtags
                    $encodedURL = str_replace('%23', '#',$encodedURL);
                    $params = array(
                        'rdp_we_resource' => $encodedURL
                    );
                    $link->href = esc_attr(add_query_arg($params,$permalink));                
                    $link->target = null;

                    $sQuery = parse_url($sHREF,PHP_URL_QUERY);
                    if($sQuery){
                        parse_str($sQuery, $output);
                        if(key_exists('rdp_we_resource', $output)){
                            $sHREF = $output['rdp_we_resource'];
                        }
                    }

                    if(!$fIsFile){
                        $att = 'data-href';
                        $link->$att = esc_attr($sHREF);
                        $att = 'data-title';
                        $link->$att = esc_attr($link->innertext);                    
                        $link->title = esc_attr($link->innertext);                        
                    }
            }

        }  

        $sCite = 'cite_note';
        $len = strlen($sCite);
        foreach($html->find('[id]') as $element){
            if(substr($element->id, 0, $len) === $sCite)continue;
            if($element->class){
                if(strpos($element->class, 'mw-headline') !== false)continue;
            }
            $element->id = 'rdp-we-' . $element->id;
        }               
        
        $this->_content = $html->find('body',0)->innertext;        
    }//preRender
    
    private function scrub(&$body) {
        $remove_elements = array(
            '#jump-to-nav',
            '#column-one',
            '#siteSub',
            '#contentSub',
            '#catlinks',
            'script',
            'style',
            'link[rel=stylesheet]',
            '.mw-inputbox-centered',
            'table.plainlinks',
            'div.plainlinks',
            '.mw-indicators',
            'form.header',
            '#page-actions',
            'math',
            '.mw-jump-link',
        );
        
        
        $remove_elements = apply_filters('rdp_we_scrub_remove_elements_filter', $remove_elements);
        
        foreach ( $remove_elements as $element ) {
            foreach($body->find($element) as $e) 
            {
                $e->outertext = '';
            }            
        }
        
        foreach($body->find('table') as $e) 
        {
            $e->style = str_replace('float:right;', '', $e->style);
        } 
        
        $oURLPieces = parse_url($this->url);
               
        if(empty($oURLPieces['scheme']))$oURLPieces['scheme'] = 'http';        
     
        foreach($body->find('img') as $img){
            $oImgPieces = parse_url($img->src);
            if(!is_array($oImgPieces)){
                $img->outertext = '';
                continue;
            }
            if(!key_exists('path', $oImgPieces)){
                $img->outertext = '';
                continue;                
            }    
            
            $sPath = $oImgPieces['path'];
            if(substr($sPath, 0, 3) == '../')$sPath = substr($sPath, 3);
            $oImgPieces['path'] = RDP_WIKI_EMBED_UTILITIES::leadingslashit($sPath);            
            
            if(!isset($oImgPieces['host'])):
                $oImgPieces['host'] = $oURLPieces['host'];
            endif;
            
            if(!isset($oImgPieces['scheme'])):
                $oImgPieces['scheme'] = $oURLPieces['scheme'];
            endif;
            
            $img->src = RDP_WIKI_EMBED_UTILITIES::unparse_url($oImgPieces);
            
            $data = 'data-file-width';
            $img->$data = null;
            $data = 'data-file-height';
            $img->$data = null;
            $img->srcset = null;
            if($img->width >= 400 || $img->height >= 400){
               $img->width = null;
               $img->height = null;
               $img->style = 'width: 100%;max-width: 400px;height: auto;';
            }
        }
        
        foreach($body->find('a') as $link){
            if(!isset($link->href) || substr($link->href, 0, 1) === '#') continue;

            $link->href = RDP_WIKI_EMBED_UTILITIES::entitiesPlain($link->href);
            $oLinkPieces = parse_url($link->href); 
            if(!is_array($oLinkPieces))  continue;
            $sQuery = parse_url($link->href,PHP_URL_QUERY);
            $oQueryPieces = array();
            parse_str($sQuery,$oQueryPieces);
            
            $pos = strpos($link->href, 'Special:');
            $fIsSpecial = !($pos === false);
            if($fIsSpecial){
                $link->outertext = $link->innertext;
                continue;
            }
          
            if(strtolower($link->innertext) == 'printable version'){
                $link->class = $link->class . ' external';

            }
            
            if(is_array($oQueryPieces) && isset($oQueryPieces['action']) && strtolower($oQueryPieces['action']) == 'edit'){
                if($this->_options['edit_show']):
                    $link->class = $link->class . ' external';
                else:
                    $link->outertext = '';
                    continue;
                endif;
            }

            if(key_exists('path', $oLinkPieces)):
                $sPath = $oLinkPieces['path'];
                if(substr($sPath, 0, 3) == '../')$sPath = substr($sPath, 3);
                if(substr($sPath, 0, 2) == '..')$sPath = substr($sPath, 2);
                if(substr($sPath, 0, 1) != '/')$sPath = '/'.$sPath;
                $oLinkPieces['path'] = $sPath;                
            endif;
            
            if(!isset($oLinkPieces['scheme'])):
                $oLinkPieces['scheme'] = $oURLPieces['scheme'];
            endif;  

            if(!isset($oLinkPieces['host'])):
                $oLinkPieces['host'] = $oURLPieces['host'];
            endif;

            $link->href = RDP_WIKI_EMBED_UTILITIES::unparse_url($oLinkPieces);
        }  
    }//content_scrub
    
    private function content_get(&$wpdb,$table) {
        
        $curl = curl_init();
        // Make the request
        curl_setopt($curl, CURLOPT_URL, $this->url );
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');        
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_COOKIEFILE, "");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT ,0); 
        curl_setopt($curl, CURLOPT_TIMEOUT, 400); //timeout in seconds        
        set_time_limit(0);
        $response = curl_exec($curl);
        if (FALSE === $response):
            $this->_lastMessage = curl_error($curl);
            $this->_hasError = true ;
            return false;            
        endif;

        curl_close($curl);

        $html = new rdp_simple_html_dom();
        $html->load($response,true,false);
        //$html = new rdp_simple_html_dom($response); // Create new parser instance        

        if(!$html){
            $this->_hasError = true; 
            $msg = esc_html__('Unable to retrieve wiki contents.', 'rdp-wiki-embed');
            $this->_lastMessage = $msg;
            return;
        }
        
        $body = $html->find('body',0);
        if(!$body){
            $this->_hasError = true;
            $msg = esc_html__('Unable to locate body of wiki content.', 'rdp-wiki-embed');
            $this->_lastMessage = $msg;
            return;            
        }
     
        $this->scrub($body);
        $this->_contentRaw = $body->outertext;        
        $options = get_option( RDP_WIKI_EMBED_PLUGIN::$options_name );
        if(!is_array($options)){
            $options = RDP_WIKI_EMBED_PLUGIN::default_settings();
        }
        
        if(empty($options['wiki_update']))return;
        
        $wpdb->update( 
                $table, 
                array( 
                        'wiki_content' => $this->_contentRaw,
                        'date_created' => current_time( 'mysql' )
                ), 
                array( 'wiki_key' => $this->_key ), 
                array( 
                        '%s',	
                        '%s'	
                ), 
                array( '%s' ) 
        ); 
        
        if($wpdb->rows_affected != 0)return;
        $wpdb->insert( 
                $table, 
                array( 
                        'wiki_key' => $this->_key, 
                        'wiki_content' => $this->_contentRaw,
                        'date_created' => current_time( 'mysql' )
                ), 
                array( 
                        '%s',
                        '%s',
                        '%s' 
                ) 
        );            
    }//content_get
    
    private function isExpired() {
        $date = new DateTime($this->_dateCreated);
        $update = intval($this->_options['wiki_update']);
        $i = DateInterval::createFromDateString($update . ' minutes');            
        $date->add($i); 
        return (new DateTime() > $date);
    }//isExpired
    
}//RDP_WIKI_EMBED_CONTENT


/*EOF */
