package nro.virtualplayer;

import Utils.Logger;
import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.List;

/**
 * Câu chat tùy chỉnh cho Virtual Player.
 * File: data/virtualplayer_chat.txt (UTF-8, 1 câu/dòng, # = comment).
 * Được thêm/xóa từ Web Admin. Bot sẽ dùng các câu này khi trò chuyện.
 */
public class VirtualChatConfig {

    private static VirtualChatConfig instance;

    private final List<String> lines = new ArrayList<>();

    private static final String CHAT_FILE = "data" + File.separator + "virtualplayer_chat.txt";

    private VirtualChatConfig() {
    }

    public static synchronized VirtualChatConfig gI() {
        if (instance == null) {
            instance = new VirtualChatConfig();
            instance.load();
        }
        return instance;
    }

    public void load() {
        synchronized (lines) {
            lines.clear();
            try {
                File f = new File(CHAT_FILE);
                if (!f.exists()) return;
                try (BufferedReader br = new BufferedReader(new InputStreamReader(new FileInputStream(f), StandardCharsets.UTF_8))) {
                    String line;
                    while ((line = br.readLine()) != null) {
                        line = line.trim();
                        if (line.isEmpty() || line.startsWith("#")) continue;
                        lines.add(line);
                    }
                }
            } catch (Exception e) {
                Logger.logException(VirtualChatConfig.class, e);
            }
        }
    }

    public void add(String msg) {
        if (msg == null) return;
        msg = msg.trim();
        if (msg.isEmpty() || msg.length() > 120) return;
        synchronized (lines) {
            if (lines.contains(msg)) return; // không trùng
            lines.add(msg);
            save();
        }
    }

    public boolean remove(int idx) {
        synchronized (lines) {
            if (idx < 0 || idx >= lines.size()) return false;
            lines.remove(idx);
            save();
            return true;
        }
    }

    public List<String> getLines() {
        synchronized (lines) {
            return new ArrayList<>(lines);
        }
    }

    public int count() {
        synchronized (lines) {
            return lines.size();
        }
    }

    public boolean isEmpty() {
        synchronized (lines) {
            return lines.isEmpty();
        }
    }

    /**
     * Lấy câu ngẫu nhiên từ danh sách tùy chỉnh (null nếu trống).
     */
    public String randomLine() {
        synchronized (lines) {
            if (lines.isEmpty()) return null;
            int idx = java.util.concurrent.ThreadLocalRandom.current().nextInt(lines.size());
            return lines.get(idx);
        }
    }

    private void save() {
        try {
            File f = new File(CHAT_FILE);
            f.getParentFile().mkdirs();
            try (BufferedWriter bw = new BufferedWriter(new OutputStreamWriter(new FileOutputStream(f), StandardCharsets.UTF_8))) {
                bw.write("# Cau chat tuy chinh cua Virtual Player (1 cau/dong, # = ghi chu)");
                bw.newLine();
                for (String s : lines) {
                    bw.write(s);
                    bw.newLine();
                }
            }
        } catch (Exception e) {
            Logger.logException(VirtualChatConfig.class, e);
        }
    }
}
