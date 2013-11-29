<?php
namespace MeroPhp\Swf;

/**
 * Gets information about an swf file
 */
class SwfHeader
{
    var $fname; // SWF file analyzed
    var $magic; // Magic in a SWF file (FWS or CWS)
    var $compressed; // Flag to indicate a compressed file (CWS)
    var $version; // Flash version
    var $size; // Uncompressed file size (in bytes)
    var $width; // Flash movie native width
    var $height; // Flash movie native height
    var $valid; // Valid SWF file
    var $fps; // Flash movie native frame-rate
    var $frames; // Flash movie total frames
    var $isAvm2; // AVM1 (AS1 and AS2) or AVM2 (AS3)
    var $as3;

    public function __construct()
    {
        $this->valid = false;
        $this->fname = "";
        $this->magic = "";
        $this->compressed = false;
        $this->version = 0;
        $this->width = 0;
        $this->height = 0;
        $this->size = 0;
        $this->frames = 0;
        $this->fps[] = Array();
    }

    public function loadSWF($filename)
    {
        $this->fname = $filename;
        
        $fp = @fopen($filename, "rb");
        if($fp){
            
            // Read MAGIC FIELD
            $this->magic = fread($fp, 3);
            
            if($this->magic != "FWS" && $this->magic != "CWS"){
                $this->valid = false;
            }else{
                
                // Compression
                if(substr($this->magic, 0, 1) == "C"){
                    $this->compressed = true;
                }else{
                    $this->compressed = false;
                }
                
                // Version
                $this->version = ord(fread($fp, 1));
                
                // Size
                $lg = 0;
                
                // 4 LSB-MSB
                for($i = 0; $i < 4; $i ++){
                    $t = ord(fread($fp, 1));
                    $lg += ($t << (8 * $i));
                }
                $this->size = $lg;
                
                // RECT... we will "simulate" a stream from now on... read
                // remaining file
                $buffer = fread($fp, $this->size);
                
                if($this->compressed){
                    // First decompress GZ stream
                    $buffer = gzuncompress($buffer, $this->size);
                }
                
                $b = ord(substr($buffer, 0, 1));
                $buffer = substr($buffer, 1);
                $cbyte = $b;
                $bits = $b >> 3;
                $cval = "";
                
                // Current byte
                $cbyte &= 7;
                $cbyte <<= 5;
                
                // Current bit (first byte starts off already shifted)
                $cbit = 2;
                
                // Must get all 4 values in the RECT
                for($vals = 0; $vals < 4; $vals ++){
                    $bitcount = 0;
                    while($bitcount < $bits){
                        if($cbyte & 128){
                            $cval .= "1";
                        }else{
                            $cval .= "0";
                        }
                        $cbyte <<= 1;
                        $cbyte &= 255;
                        $cbit --;
                        $bitcount ++;
                        
                        // We will be needing a new byte if we run out of bits
                        if($cbit < 0){
                            $cbyte = ord(substr($buffer, 0, 1));
                            $buffer = substr($buffer, 1);
                            $cbit = 7;
                        }
                    }
                    
                    // O.k. full value stored... calculate
                    $c = 1;
                    $val = 0;
                    
                    // Reverse string to allow for SUM(2^n*$atom)
                    $tval = strrev($cval);
                    
                    for($n = 0; $n < strlen($tval); $n ++){
                        $atom = substr($tval, $n, 1);
                        if($atom == "1")
                            $val += $c;
                            
                            // 2^n
                        $c *= 2;
                    }
                    
                    // TWIPS to PIXELS
                    $val /= 20;
                    
                    switch($vals){
                        case 0:
                            // tmp value
                            $this->width = $val;
                            break;
                        
                        case 1:
                            $this->width = $val - $this->width;
                            break;
                        
                        case 2:
                            // tmp value
                            $this->height = $val;
                            break;
                        
                        case 3:
                            $this->height = $val - $this->height;
                            break;
                    }
                    $cval = "";
                }
                
                // Frame rate
                $this->fps = Array();
                
                for($i = 0; $i < 2; $i ++){
                    $t = ord(substr($buffer, 0, 1));
                    $buffer = substr($buffer, 1);
                    $this->fps[] = $t;
                }
                
                // Frames
                $this->frames = 0;
                
                for($i = 0; $i < 2; $i ++){
                    $t = ord(substr($buffer, 0, 1));
                    $buffer = substr($buffer, 1);
                    $this->frames += ($t << (8 * $i));
                }
                
                while(strlen($buffer) > 0){
                    // we read two bytes
                    // the first 10 bit is the tag code
                    // the last 6 bit is the length
                    $a = ord(substr($buffer, 0, 1));
                    $buffer = substr($buffer, 1);
                    $b = ord(substr($buffer, 0, 1));
                    $buffer = substr($buffer, 1);
                    
                    $c = $a;
                    $c = $c << 8;
                    $c = $c | $b;
                    
                    $tagcode = $c >> 6;
                    $taglen = $c & 0x3f;
                    
                    if($taglen >= 63){
                        $buffer = substr($buffer, 0, 4);
                    }else{
                        $tmp = ord(substr($buffer, 0, 1));
                        $buffer = substr($buffer, 0, 1);
                    }
                    
                    // if ($tagcode == 69) {
                    if($tagcode == 272){
                        // finaly we found it
                        // here we have to get the 4th bit
                        // $tag = ord(substr($buffer, 0, 1));
                        $this->as3 = $tmp & 8;
                        // exiting the loop
                        break;
                    }
                }
                
                fclose($fp);
                $this->valid = true;
            }
        }else{
            $this->valid = false;
        }
        
        return $this->valid;
    }
}
