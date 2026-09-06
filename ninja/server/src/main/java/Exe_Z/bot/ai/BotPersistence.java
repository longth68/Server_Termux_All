package Exe_Z.bot.ai;

import java.io.File;
import java.io.FileReader;
import java.io.FileWriter;
import java.util.ArrayList;
import java.util.List;
import org.json.simple.JSONArray;
import org.json.simple.JSONObject;
import org.json.simple.parser.JSONParser;

/**
 * Port từ NRO VirtualPersistence: lưu bot_save.json (không đụng DB schoolzz).
 * Đảm bảo BOT AI hoạt động tốt sau restart, kiểm tra file thay vì migration DB.
 */
public class BotPersistence {

    private static final String FILE = "ninja/server/bot_save.json";
    private static final String FILE_FALLBACK = "bot_save.json";

    @SuppressWarnings("unchecked")
    public static synchronized void saveAll(List<JSONObject> bots) {
        if (bots == null) {
            return;
        }
        JSONArray arr = new JSONArray();
        arr.addAll(bots);
        for (String path : new String[]{FILE, FILE_FALLBACK}) {
            try (FileWriter w = new FileWriter(path)) {
                w.write(arr.toJSONString());
                return;
            } catch (Exception ignored) {
            }
        }
    }

    public static synchronized JSONArray loadAll() {
        for (String path : new String[]{FILE, FILE_FALLBACK}) {
            try {
                File f = new File(path);
                if (!f.exists() || f.length() == 0) {
                    continue;
                }
                try (FileReader r = new FileReader(f)) {
                    Object o = new JSONParser().parse(r);
                    if (o instanceof JSONArray) {
                        return (JSONArray) o;
                    }
                }
            } catch (Exception ignored) {
            }
        }
        return new JSONArray();
    }

    public static List<JSONObject> snapshot(List<? extends Object> bots) {
        return new ArrayList<>();
    }
}
