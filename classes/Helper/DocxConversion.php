<?php

class DocxConversion
{
    private $filename;

    public function __construct($filePath)
    {
        $this->filename = $filePath;
    }

    private function read_doc()
    {
        if (($fh = fopen($this->filename, 'r')) !== false) {

            $headers = fread($fh, 0xA00);

            $n1 = (ord($headers[0x21C]) - 1);

            $n2 = ((ord($headers[0x21D]) - 8) * 256);

            $n3 = ((ord($headers[0x21E]) * 256) * 256);

            $n4 = (((ord($headers[0x21F]) * 256) * 256) * 256);


            $textLength = ($n1 + $n2 + $n3 + $n4);

            $extracted_plaintext = fread($fh, $textLength);
        }

        return utf8_encode($extracted_plaintext);
    }

    private function read_docx()
    {

        $striped_content = '';
        $content = '';

        $zip = zip_open($this->filename);

        if (!$zip || is_numeric($zip)) return false;

        while ($zip_entry = zip_read($zip)) {

            if (zip_entry_open($zip, $zip_entry) == FALSE) continue;

            if (zip_entry_name($zip_entry) != "word/document.xml") continue;

            $content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

            zip_entry_close($zip_entry);
        }// end while

        zip_close($zip);

        $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
        $content = str_replace('</w:r></w:p>', "\r\n", $content);
        $striped_content = strip_tags($content);

        return $striped_content;
    }

    /************************excel sheet************************************/

    function xlsx_to_text($input_file)
    {
        $xml_filename = "xl/sharedStrings.xml"; //content file name
        $zip_handle = new ZipArchive;
        $output_text = "";
        if (true === $zip_handle->open($input_file)) {
            if (($xml_index = $zip_handle->locateName($xml_filename)) !== false) {
                $xml_datas = $zip_handle->getFromIndex($xml_index);
                $xml_handle = DOMDocument::loadXML($xml_datas, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
                $output_text = strip_tags($xml_handle->saveXML());
            } else {
                $output_text .= "";
            }
            $zip_handle->close();
        } else {
            $output_text .= "";
        }
        return $output_text;
    }

    /*************************power point files*****************************/
    function pptx_to_text($input_file)
    {
        $zip_handle = new ZipArchive;
        $output_text = "";
        if (true === $zip_handle->open($input_file)) {
            $slide_number = 1; //loop through slide files
            while (($xml_index = $zip_handle->locateName("ppt/slides/slide" . $slide_number . ".xml")) !== false) {
                $xml_datas = $zip_handle->getFromIndex($xml_index);
                $xml_handle = DOMDocument::loadXML($xml_datas, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
                $output_text .= strip_tags($xml_handle->saveXML());
                $slide_number++;
            }
            if ($slide_number == 1) {
                $output_text .= "";
            }
            $zip_handle->close();
        } else {
            $output_text .= "";
        }
        return $output_text;
    }


    public function convertToText()
    {

        if (isset($this->filename) && !file_exists($this->filename)) {
            return "File Not exists";
        }

        $fileArray = pathinfo($this->filename);
        $file_ext = $fileArray['extension'];
        if ($file_ext == "doc" || $file_ext == "docx" || $file_ext == "xlsx" || $file_ext == "pptx") {
            if ($file_ext == "doc") {
                return $this->read_doc();
            } elseif ($file_ext == "docx") {
                return $this->read_docx();
            } elseif ($file_ext == "xlsx") {
                return $this->xlsx_to_text();
            } elseif ($file_ext == "pptx") {
                return $this->pptx_to_text();
            }
        } else {
            return "Invalid File Type";
        }
    }

}

?>