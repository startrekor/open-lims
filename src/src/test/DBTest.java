package test;

import java.sql.ResultSet;
import java.sql.SQLException;

import base.DBLink;

import static org.junit.Assert.*;

public class DBTest {

	public static void test()
	{
		testStatement();
	}
	
	private static void testStatement() 
	{
		ResultSet rs = DBLink.executeQuery("SELECT * FROM bio_organisms WHERE id='10090'");
		try 
		{
			while (rs.next()) 
			{
				assertTrue("Identifier should be 'mouse'",rs.getString("identifer").equals("mouse"));
				assertTrue("Long name should be 'Mus Musculus'",rs.getString("longname").equals("Mus Musculus"));
				assertTrue("Short name should be 'M. Musculus'",rs.getString("shortname").equals("M. Musculus"));
				assertTrue("Entry should be 'MOUSE'",rs.getString("entry").equals("MOUSE"));
			}
		} 
		catch (SQLException e) 
		{
			e.printStackTrace();
		}
	}
	
}
