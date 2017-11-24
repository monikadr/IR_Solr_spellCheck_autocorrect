
import junit.framework.TestCase;

import java.io.BufferedWriter;
import java.io.ByteArrayInputStream; 
import java.io.InputStream; 
import java.io.FileInputStream;
import java.io.FileWriter;
import java.io.IOException;
import java.io.File; 
import java.nio.charset.Charset; 
 
import org.apache.tika.parser.ParseContext; 
import org.apache.tika.parser.Parser;
import org.apache.tika.parser.html.BoilerpipeContentHandler;
import org.apache.tika.parser.html.HtmlParser; 
import org.apache.tika.sax.BodyContentHandler;
import org.apache.tika.exception.TikaException;
import org.apache.tika.metadata.Metadata; 
import org.apache.tika.parser.AutoDetectParser; 
import org.apache.tika.sax.LinkContentHandler; 
import org.apache.tika.sax.TeeContentHandler; 
import org.apache.tika.sax.ToHTMLContentHandler; 
import org.junit.*; 
import org.xml.sax.ContentHandler;
import org.xml.sax.SAXException; 



public class parser_tika {

	public void parser_tika() throws IOException, SAXException, TikaException {
		File dir = new File("C:/Users/Monika/Desktop/CS572 - Info. Retrieval/My Assignments/hw4/crawl_data/NYD/NYD/");
//		BufferedWriter buf = new BufferedWriter(new FileWriter("C:/Users/Monika/Desktop/CS572 - Info. Retrieval/My Assignments/hw5/big.txt"));
		int count=0;
		for(File file : dir.listFiles()){
    		count++;
		InputStream input = new FileInputStream(file);
		ContentHandler handler = new BodyContentHandler();
        Metadata metadata = new Metadata();
        
//        new HtmlParser().parse(input, new BoilerpipeContentHandler(handler), metadata);
        new HtmlParser().parse(input,handler,metadata,new ParseContext());
        System.out.println("text:\n" + handler.toString());
//        buf.write(handler.toString());
//        buf.newLine();
        String[] metadataNames = metadata.names();
//        
        for(String name : metadataNames) {
           System.out.println(name + ":   " + metadata.get(name));  
        }
    	}
//        buf.close();
        
        System.out.println(count);
	}  
	@Test
	public void testHTML() throws Exception{
		parser_tika();
	}
	
}