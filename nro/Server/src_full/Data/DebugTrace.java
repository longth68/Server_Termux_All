package Data;

import java.io.FileWriter;
import java.io.IOException;
import java.io.PrintWriter;

/**
 * Debug trace ra file (tam thoi, de chuan doan client JAR).
 */
public class DebugTrace {

    private static final String FILE = "debug_client.log";

    public static synchronized void log(String s) {
        try (PrintWriter pw = new PrintWriter(new FileWriter(FILE, true))) {
            pw.println(System.currentTimeMillis() + " | " + s);
        } catch (IOException e) {
        }
    }
}
