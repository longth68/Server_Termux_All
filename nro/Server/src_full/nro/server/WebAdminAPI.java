package nro.server;

import com.sun.net.httpserver.HttpServer;
import com.sun.net.httpserver.HttpHandler;
import com.sun.net.httpserver.HttpExchange;
import java.io.FileWriter;
import java.io.IOException;
import java.io.OutputStream;
import java.io.PrintWriter;
import java.net.InetSocketAddress;
import java.lang.management.ManagementFactory;
import com.sun.management.OperatingSystemMXBean;
import java.util.Calendar;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.concurrent.Executors;
import java.util.concurrent.ScheduledExecutorService;
import java.util.concurrent.ScheduledFuture;
import java.util.concurrent.TimeUnit;
import java.lang.reflect.Field;
import java.lang.reflect.Modifier;
import QuanLiBoss.Boss;
import QuanLiBoss.BossID;
import QuanLiBoss.BossStatus;
import QuanLiBoss.Manager.BossManager;
import event.EventManager;
import nro.bot.BotManager;
import nro.services.Service;
import nro.services.PlayerService;
import nro.services.TaskService;
import nro.inventory.InventoryService;
import models.Item.Item;
import models.Item.ItemOption;
import models.Item.ItemService;
import jbcd.data.DatabaseUpdater;
import nro.player.Player;
import nro.player.NPoint;
import nro.task.TaskMain;
import nro.server.Client;

public class WebAdminAPI {
    private static HttpServer server;
    // Port API cau hinh qua JVM property (Termux: -Danwin.api.port=8085).
    // Mac dinh 8888 de khong doi hanh vi chay tren PC.
    private static final int PORT = Integer.getInteger("anwin.api.port", 8888);
    private static long startTime = System.currentTimeMillis();
    private static ScheduledExecutorService maintenanceScheduler;
    private static ScheduledFuture<?> maintenanceTask;
    private static int maintHour = -1, maintMinute = -1;
    private static boolean maintAutoRestart = false;

    public static void start() {
        try {
            server = HttpServer.create(new InetSocketAddress("0.0.0.0", PORT), 0);
            loadMaintenanceConfig();
            
            // L�º¥y th�ng tin th�»‘ng k?ª
            server.createContext("/api/info", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    try {
                        OperatingSystemMXBean osBean = ManagementFactory.getPlatformMXBean(OperatingSystemMXBean.class);
                        double cpuLoad = osBean.getSystemCpuLoad() * 100.0;
                        if (cpuLoad < 0) cpuLoad = 0; // fallback

                        long ramUsed = (Runtime.getRuntime().totalMemory() - Runtime.getRuntime().freeMemory()) / (1024 * 1024);
                        long ramMax = Runtime.getRuntime().maxMemory() / (1024 * 1024);
                        
                        long uptimeMillis = System.currentTimeMillis() - startTime;
                        long days = uptimeMillis / (24 * 3600000);
                        uptimeMillis = uptimeMillis % (24 * 3600000);
                        long hours = uptimeMillis / 3600000;
                        uptimeMillis %= 3600000;
                        long mins = uptimeMillis / 60000;
                        long secs = (uptimeMillis % 60000) / 1000;
                        String uptime = String.format("%dd %02dh %02dm %02ds", days, hours, mins, secs);

                        int alive = 0, respawn = 0, wait = 0;
                        try {
                            int[] stats = BossManager.gI().getBossStatusCounts();
                            alive = stats[0];
                            respawn = stats[1];
                            wait = stats[2];
                        } catch(Exception e) {}

                        String response = "{";
                        // Using Locale.US to prevent comma instead of dot in decimal format!
                        response += "\"cpu\": " + String.format(java.util.Locale.US, "%.1f", cpuLoad) + ",";
                        response += "\"ram_used\": " + ramUsed + ",";
                        response += "\"ram_max\": " + ramMax + ",";
                        response += "\"threads\": " + Thread.activeCount() + ",";
                        response += "\"sessions\": " + Client.gI().getPlayers().size() + ",";
                        response += "\"uptime\": \"" + uptime + "\",";
                        response += "\"bot_enabled\": " + BotManager.BOT_SYSTEM_ENABLED + ",";
                        response += "\"rate_exp\": " + Manager.RATE_EXP_SERVER + ",";
                        response += "\"maintenance\": " + Maintenance.isRunning + ",";
                        response += "\"data_mode\": " + (Manager.readInt ? 1 : 0) + ",";
                        response += "\"boss_alive\": " + alive + ",";
                        response += "\"boss_respawn\": " + respawn + ",";
                        response += "\"boss_wait\": " + wait;
                        response += "}";
                        sendResponse(exchange, response);
                    } catch (Throwable t) {
                        t.printStackTrace();
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Internal Server Error\"}");
                    }
                }
            });

            server.createContext("/api/maintenance", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Maintenance.gI().start(120);
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"B?t ??u b?o tr� sau 2 ph�t!\"}");
                }
            });

            server.createContext("/api/maintenance_start", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    int minutes = 2;
                    if (query.containsKey("val")) {
                        try {
                            minutes = Integer.parseInt(query.get("val"));
                            if (minutes < 1) minutes = 1;
                        } catch (Exception e) {}
                    }
                    if (!Maintenance.isRunning) {
                        Maintenance.gI().start(minutes);
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� l�n l?ch b?o tr� sau " + minutes + " ph�t!\"}");
                    } else {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"B?o tr� ?ang ch?y, kh�ng th? l�n l?ch th�m!\"}");
                    }
                }
            });

            server.createContext("/api/maintenance_now", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    new Thread(() -> Maintenance.gI().startImmediately()).start();
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đang b?o tr� ngay l?p t?c!\"}");
                }
            });

            server.createContext("/api/reload_db", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    nro.giftcode.GiftCodeManager.gI().listGiftCode.clear();
                    nro.giftcode.GiftCodeManager.gI().init();
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� t?i l?i danh s�ch Giftcode!\"}");
                }
            });

            server.createContext("/api/reload_shop", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Manager.gI().updateShop();
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� t?i l?i d? li?u Shop!\"}");
                }
            });

            server.createContext("/api/ram_optimize", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    System.gc();
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� dọn d?p JVM RAM!\"}");
                }
            });

            server.createContext("/api/bot_toggle", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    BotManager.BOT_SYSTEM_ENABLED = !BotManager.BOT_SYSTEM_ENABLED;
                    sendResponse(exchange, "{\"status\": \"success\", \"state\": " + BotManager.BOT_SYSTEM_ENABLED + ", \"msg\": \"Đ� " + (BotManager.BOT_SYSTEM_ENABLED ? "B?T" : "T?T") + " h? th?ng Bot AI.\"}");
                }
            });

            server.createContext("/api/bot_set_enabled", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    if (query.containsKey("val")) {
                        BotManager.BOT_SYSTEM_ENABLED = "1".equals(query.get("val"));
                        BotManager.ALLOW_CREATE_BOT = BotManager.BOT_SYSTEM_ENABLED;
                    }
                    sendResponse(exchange, "{\"status\": \"success\", \"state\": " + BotManager.BOT_SYSTEM_ENABLED + ", \"msg\": \"Bot " + (BotManager.BOT_SYSTEM_ENABLED ? "B?T" : "T?T") + ".\"}");
                }
            });

            server.createContext("/api/bot_status", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    String response = "{"
                        + "\"enabled\": " + BotManager.BOT_SYSTEM_ENABLED + ","
                        + "\"mob_count\": " + BotManager.gI().countByType(0) + ","
                        + "\"shop_count\": " + BotManager.gI().countByType(1) + ","
                        + "\"boss_count\": " + BotManager.gI().countByType(2) + ","
                        + "\"sell_count\": " + BotManager.gI().countByType(3) + ","
                        + "\"mob_target\": " + BotManager.TARGET_MOB_BOT + ","
                        + "\"shop_target\": " + BotManager.TARGET_SHOP_BOT + ","
                        + "\"boss_target\": " + BotManager.TARGET_BOSS_BOT + ","
                        + "\"sell_target\": " + BotManager.TARGET_SELL_BOT
                        + "}";
                    sendResponse(exchange, response);
                }
            });

            server.createContext("/api/bot_set_target", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    String type = query.get("type");
                    int val = 0;
                    if (query.containsKey("val")) {
                        try { val = Integer.parseInt(query.get("val")); if (val < 0) val = 0; } catch (Exception e) {}
                    }
                    if (type == null) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thi?u type bot!\"}");
                        return;
                    }
                    switch (type) {
                        case "mob":  BotManager.TARGET_MOB_BOT  = val; break;
                        case "shop": BotManager.TARGET_SHOP_BOT = val; break;
                        case "boss": BotManager.TARGET_BOSS_BOT = val; break;
                        case "sell": BotManager.TARGET_SELL_BOT = val; break;
                        default:
                            sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Type kh�ng h?p l? (mob/shop/boss/sell)!\"}");
                            return;
                    }
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� set target bot " + type + " = " + val + "!\"}");
                }
            });

            server.createContext("/api/bot_spawn", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    int amount = 1;
                    int type = 0;
                    if (query.containsKey("amount")) { try { amount = Integer.parseInt(query.get("amount")); } catch (Exception e) {} }
                    if (query.containsKey("type")) { try { type = Integer.parseInt(query.get("type")); } catch (Exception e) {} }
                    if (type == 1) {
                        for (int i = 0; i < amount; i++) {
                            nro.bot.ShopBot sb = new nro.bot.ShopBot(BotManager.SHOP_ITEM_ID, BotManager.SHOP_TRADE_ID, BotManager.SHOP_TRADE_NEED);
                            new nro.bot.NewBot().runBot(1, sb, null, 1);
                        }
                    } else if (type == 3) {
                        for (int i = 0; i < amount; i++) {
                            nro.bot.SellBot sl = new nro.bot.SellBot(441, 14, 99);
                            new nro.bot.NewBot().runBot(3, null, sl, 1);
                        }
                    } else {
                        for (int i = 0; i < amount; i++) {
                            new nro.bot.NewBot().runBot(type, null, null, 1);
                        }
                    }
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� gọi " + amount + " Bot (type " + type + ") th�nh c�ng!\"}");
                }
            });

            server.createContext("/api/bot_remove_all", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    BotManager.gI().stopAllBots();
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� x�a to�n b? Bot ?ang ch?y!\"}");
                }
            });

            // ================= VIRTUAL PLAYER (Bot AI th�ng minh) =================
            server.createContext("/api/vp_status", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    nro.virtualplayer.VirtualPlayerManager m = nro.virtualplayer.VirtualPlayerManager.gI();
                    nro.virtualplayer.VirtualConfig cfg = nro.virtualplayer.VirtualConfig.gI();
                    StringBuilder bots = new StringBuilder();
                    for (nro.virtualplayer.VirtualPlayer vp : m.getBots()) {
                        if (bots.length() > 0) bots.append(",");
                        String pers = "";
                        try {
                            if (vp.profile != null) {
                                for (nro.virtualplayer.core.VirtualPersonality p : vp.profile.getPersonalities()) {
                                    if (pers.length() > 0) pers += ",";
                                    pers += p.name();
                                }
                            }
                        } catch (Exception e) {}
                        long power = 0;
                        int mapId = -1;
                        try { if (vp.nPoint != null) power = vp.nPoint.power; } catch (Exception e) {}
                        try { if (vp.zone != null) mapId = vp.zone.map.mapId; } catch (Exception e) {}
                        bots.append("{")
                            .append("\"id\":").append(vp.id).append(",")
                            .append("\"name\":\"").append(vp.name.replace("\\", "\\\\").replace("\"", "\\\"")).append("\",")
                            .append("\"power\":").append(power).append(",")
                            .append("\"state\":\"").append(vp.state != null ? vp.state.name() : "NULL").append("\",")
                            .append("\"map_id\":").append(mapId).append(",")
                            .append("\"online\":").append(safeIsOnline(vp)).append(",")
                            .append("\"visiting\":").append(safeHasPresence(vp)).append(",")
                            .append("\"host_id\":").append(safeHostId(vp)).append(",")
                            .append("\"pers\":\"").append(pers).append("\"")
                            .append("}");
                    }
                    String response = "{"
                        + "\"enabled\": " + m.isSystemEnabled() + ","
                        + "\"count\": " + m.count() + ","
                        + "\"online_count\": " + m.getOnlineBots().size() + ","
                        + "\"target\": " + m.getTargetPopulation() + ","
                        + "\"exp_rate\": " + cfg.expRate + ","
                        + "\"gold_rate\": " + cfg.goldRate + ","
                        + "\"chat_rate\": " + cfg.chatRate + ","
                        + "\"map_change_rate\": " + cfg.mapChangeRate + ","
                        + "\"gift_rate\": " + cfg.giftRate + ","
                        + "\"afk_rate\": " + cfg.afkRate + ","
                        + "\"player_protection\": " + cfg.playerProtection + ","
                        + "\"presence_per_player\": " + cfg.presencePerPlayer + ","
                        + "\"presence_visit_seconds\": " + cfg.presenceVisitSeconds + ","
                        + "\"bots\": [" + bots.toString() + "]"
                        + "}";
                    sendResponse(exchange, response);
                }

                private boolean safeIsOnline(nro.virtualplayer.VirtualPlayer vp) {
                    try { return vp.isOnline(); } catch (Exception e) { return false; }
                }

                private boolean safeHasPresence(nro.virtualplayer.VirtualPlayer vp) {
                    try { return vp.hasActivePresence(); } catch (Exception e) { return false; }
                }

                private long safeHostId(nro.virtualplayer.VirtualPlayer vp) {
                    try { return vp.hasActivePresence() ? vp.presenceHostId : 0; } catch (Exception e) { return 0; }
                }
            });

            // Tom tat HIEN DIEN LUAN PHIEN: moi player that co bao nhieu bot dang o cung khu / dang ghe tham
            server.createContext("/api/vp_presence", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    try {
                        nro.virtualplayer.VirtualConfig cfg = nro.virtualplayer.VirtualConfig.gI();
                        java.util.List<nro.virtualplayer.VirtualPlayer> bots =
                            nro.virtualplayer.VirtualPlayerManager.gI().getBots();

                        int visitingTotal = 0;
                        for (nro.virtualplayer.VirtualPlayer vp : bots) {
                            try { if (vp.hasActivePresence()) visitingTotal++; } catch (Exception e) {}
                        }

                        StringBuilder arr = new StringBuilder();
                        int realCount = 0;
                        for (Player pl : Client.gI().getPlayersSnapshot()) {
                            if (pl == null || pl.isBot || pl.isBoss || pl.isDeTu || !pl.isPlayer) continue;
                            if (pl.zone == null || pl.zone.map == null) continue;
                            realCount++;
                            int inZone = 0, visiting = 0;
                            for (nro.virtualplayer.VirtualPlayer vp : bots) {
                                try {
                                    if (!vp.isOnline()) continue;
                                    if (vp.zone == pl.zone) inZone++;
                                    if (vp.hasActivePresence() && vp.presenceHostId == pl.id) visiting++;
                                } catch (Exception e) {}
                            }
                            int mapId = -1;
                            try { mapId = pl.zone.map.mapId; } catch (Exception e) {}
                            if (arr.length() > 0) arr.append(",");
                            arr.append("{")
                               .append("\"id\":").append(pl.id).append(",")
                               .append("\"name\":\"").append(jsonEscape(pl.name == null ? "" : pl.name)).append("\",")
                               .append("\"map_id\":").append(mapId).append(",")
                               .append("\"bots_in_zone\":").append(inZone).append(",")
                               .append("\"visiting\":").append(visiting)
                               .append("}");
                        }
                        String response = "{\"status\": \"success\""
                            + ", \"enabled\": " + (cfg.presencePerPlayer > 0)
                            + ", \"target\": " + cfg.presencePerPlayer
                            + ", \"visit_seconds\": " + cfg.presenceVisitSeconds
                            + ", \"real_players\": " + realCount
                            + ", \"visiting_total\": " + visitingTotal
                            + ", \"players\": [" + arr.toString() + "]}";
                        sendResponse(exchange, response);
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Loi: "
                            + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            server.createContext("/api/vp_set_enabled", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    if (query.containsKey("val")) {
                        nro.virtualplayer.VirtualPlayerManager.gI().setSystemEnabled("1".equals(query.get("val")));
                    }
                    boolean on = nro.virtualplayer.VirtualPlayerManager.gI().isSystemEnabled();
                    sendResponse(exchange, "{\"status\": \"success\", \"state\": " + on + ", \"msg\": \"Virtual Player " + (on ? "B?T" : "T?T") + ".\"}");
                }
            });

            server.createContext("/api/vp_set_population", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    int val = -1;
                    if (query.containsKey("val")) {
                        try { val = Integer.parseInt(query.get("val")); } catch (Exception e) {}
                    }
                    if (val < 0 || val > 200) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Population ph?i trong kho?ng 0-200!\"}");
                        return;
                    }
                    nro.virtualplayer.VirtualPlayerManager.gI().setTargetPopulation(val);
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� set d�n s? Virtual Player = " + val + "!\"}");
                }
            });

            server.createContext("/api/vp_spawn", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    int amount = 1;
                    if (query.containsKey("amount")) {
                        try { amount = Integer.parseInt(query.get("amount")); } catch (Exception e) {}
                    }
                    if (amount < 1 || amount > 50) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"S? l??ng ph?i trong kho?ng 1-50!\"}");
                        return;
                    }
                    int created = 0;
                    for (int i = 0; i < amount; i++) {
                        try {
                            if (nro.virtualplayer.VirtualPlayerManager.gI().createBot() != null) created++;
                        } catch (Exception e) {}
                    }
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� t?o " + created + "/" + amount + " Virtual Player!\"}");
                }
            });

            server.createContext("/api/vp_remove", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = -1;
                    if (query.containsKey("id")) {
                        try { id = Long.parseLong(query.get("id")); } catch (Exception e) {}
                    }
                    if (id < 0) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thi?u ho?c sai ID bot!\"}");
                        return;
                    }
                    boolean ok = nro.virtualplayer.VirtualPlayerManager.gI().removeBot(id);
                    sendResponse(exchange, ok
                        ? "{\"status\": \"success\", \"msg\": \"Đ� x�a bot ID " + id + "!\"}"
                        : "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y bot ID " + id + "!\"}");
                }
            });

            server.createContext("/api/vp_remove_all", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    nro.virtualplayer.VirtualPlayerManager.gI().removeAllBots();
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� x�a to�n b? Virtual Player!\"}");
                }
            });

            server.createContext("/api/vp_save", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    nro.virtualplayer.VirtualPlayerManager.gI().saveNow();
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� l?u tr?ng th�i to�n b? Virtual Player!\"}");
                }
            });

            server.createContext("/api/vp_refresh_rank", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    nro.virtualplayer.VirtualPlayerManager.gI().injectRankings();
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� c?p nh?t b?ng x?p h?ng S?c m?nh!\"}");
                }
            });

            // X�a TO�N B? t�i kho?n + nh�n v?t ng?ời ch?i trong DB (gi? t�i kho?n admin)
            server.createContext("/api/wipe_accounts", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    String keep = query.containsKey("keep") ? query.get("keep") : "";
                    if (!keep.matches("[a-zA-Z0-9_]*")) keep = "";
                    if (!keep.isEmpty()) keep = keep.replace("'", "");

                    try {
                        // 1. Kick to�n b? ng?ời ch?i ?ang online ?? server kh�ng l?u ?� d? li?u ?� x�a
                        int kicked = 0;
                        for (Player p : Client.gI().getPlayersSnapshot()) {
                            try {
                                if (p == null || !p.isPl()) continue;
                                Client.gI().kickSession(p.getSession());
                                kicked++;
                            } catch (Exception e) {}
                        }
                        Thread.sleep(1500);

                        // 2. X�a d? li?u DB (gi? l?i t�i kho?n admin + username ???c gi?)
                        // M?i b?ng ch?y ??c l?p - b?ng kh�ng t?n t?i ch? b? bỏ qua
                        String condAcc = "is_admin = 0" + (keep.isEmpty() ? "" : " AND username != '" + keep + "'");
                        int delAccount = 0, delPlayer = 0, delClan = 0;
                        StringBuilder skipped = new StringBuilder();

                        // 2a. B?ng li�n quan theo player_id (ph?i x�a TR?�?C khi x�a player)
                        String[] playerLinked = {
                            "DELETE FROM shop_ky_gui WHERE player_id IN (SELECT id FROM player WHERE account_id IN (SELECT id FROM account WHERE " + condAcc + "))",
                            "DELETE FROM giftcode_save WHERE player_id IN (SELECT id FROM player WHERE account_id IN (SELECT id FROM account WHERE " + condAcc + "))",
                            "DELETE FROM cvh_history_giftcode WHERE player_id IN (SELECT id FROM player WHERE account_id IN (SELECT id FROM account WHERE " + condAcc + "))",
                            "DELETE FROM history_receive_goldbar WHERE player_id IN (SELECT id FROM player WHERE account_id IN (SELECT id FROM account WHERE " + condAcc + "))",
                            "DELETE FROM gift WHERE player_id IN (SELECT id FROM player WHERE account_id IN (SELECT id FROM account WHERE " + condAcc + "))",
                            "DELETE FROM naptien WHERE uid NOT IN (SELECT id FROM account)"
                        };
                        for (String q : playerLinked) {
                            try { jbcd.ConnectDB.executeUpdate(q); }
                            catch (Exception e) { skipped.append(" ").append(q.split(" ")[2]); }
                        }

                        // 2b. L?ch s? / giao d?ch to�n c?c (theo username ho?c kh�ng c?n ?iều ki?n)
                        String[] globalLogs = {
                            "DELETE FROM history_transaction",
                            "DELETE FROM history_bank",
                            "DELETE FROM history_exchange",
                            "DELETE FROM history_gold",
                            "DELETE FROM history_active",
                            "DELETE FROM vp_bank",
                            "DELETE FROM napthe",
                            "DELETE FROM moc_nap",
                            "DELETE FROM cvh_recharge",
                            "DELETE FROM cvh_messages",
                            "DELETE FROM cvh_sell_item",
                            "DELETE FROM chan_le",
                            "DELETE FROM tai_xiu",
                            "DELETE FROM pariry_players",
                            "DELETE FROM pariry_session"
                        };
                        for (String q : globalLogs) {
                            try { jbcd.ConnectDB.executeUpdate(q); }
                            catch (Exception e) { skipped.append(" ").append(q.split(" ")[2]); }
                        }

                        // 2c. Bang h?i (m? c�i khi h?t th�nh vi�n)
                        try { delClan = jbcd.ConnectDB.executeUpdate("DELETE FROM clan"); }
                        catch (Exception e) { skipped.append(" clan"); }

                        // 2d. Nh�n v?t + t�i kho?n (b?t bu?c ph?i x�a ???c)
                        try {
                            delPlayer = jbcd.ConnectDB.executeUpdate(
                                "DELETE FROM player WHERE account_id IN (SELECT id FROM account WHERE " + condAcc + ")");
                        } catch (Exception e) { throw new Exception("Kh�ng x�a ???c b?ng player: " + e.getMessage()); }

                        try {
                            delAccount = jbcd.ConnectDB.executeUpdate(
                                "DELETE FROM account WHERE " + condAcc);
                        } catch (Exception e) { throw new Exception("Kh�ng x�a ???c b?ng account: " + e.getMessage()); }

                        String skipMsg = skipped.length() > 0 ? " | Bỏ qua b?ng kh�ng t?n t?i:" + skipped : "";
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� x�a " + delAccount
                            + " t�i kho?n, " + delPlayer + " nh�n v?t, " + delClan + " bang h?i, kick "
                            + kicked + " player online" + skipMsg + "!\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"L?i x�a d? li?u: "
                            + e.getMessage().replace("\"", "'") + "\"}");
                    }
                }
            });
            // Danh s�ch c�u chat t�y ch?nh c?a bot
            server.createContext("/api/vp_chat_list", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    java.util.List<String> lines = nro.virtualplayer.VirtualChatConfig.gI().getLines();
                    StringBuilder arr = new StringBuilder();
                    for (String s : lines) {
                        if (arr.length() > 0) arr.append(",");
                        arr.append("\"").append(jsonEscape(s)).append("\"");
                    }
                    sendResponse(exchange, "{\"status\": \"success\", \"count\": " + lines.size()
                        + ", \"lines\": [" + arr.toString() + "]}");
                }
            });

            // Th�m c�u chat t�y ch?nh
            server.createContext("/api/vp_chat_add", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    String msg = query.containsKey("msg") ? query.get("msg").trim() : "";
                    if (msg.isEmpty() || msg.length() > 120) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"C�u chat tr?ng ho?c qu� 120 k� t?!\"}");
                        return;
                    }
                    nro.virtualplayer.VirtualChatConfig.gI().add(msg);
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� th�m c�u chat!\", \"count\": "
                        + nro.virtualplayer.VirtualChatConfig.gI().count() + "}");
                }
            });

            // X�a c�u chat theo index
            server.createContext("/api/vp_chat_del", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    int idx = -1;
                    try { idx = Integer.parseInt(query.getOrDefault("idx", "-1")); } catch (Exception e) {}
                    boolean ok = nro.virtualplayer.VirtualChatConfig.gI().remove(idx);
                    sendResponse(exchange, ok
                        ? "{\"status\": \"success\", \"msg\": \"Đ� x�a c�u chat!\"}"
                        : "{\"status\": \"error\", \"msg\": \"Index kh�ng h?p l?!\"}");
                }
            });

            // Th�ng tin chi ti?t bot + trang b? + t�i ??
            server.createContext("/api/vp_detail", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = -1;
                    try { id = Long.parseLong(query.getOrDefault("id", "-1")); } catch (Exception e) {}
                    nro.virtualplayer.VirtualPlayer vp = nro.virtualplayer.VirtualPlayerManager.gI().getBot(id);
                    if (vp == null) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y bot!\"}");
                        return;
                    }

                    int gender = 0, headId = -1, mapId = -1;
                    long power = 0, gold = 0, hp = 0, hpMax = 0, dame = 0;
                    try { gender = vp.gender; } catch (Exception e) {}
                    try { headId = vp.head; } catch (Exception e) {}
                    try { power = vp.nPoint.power; } catch (Exception e) {}
                    try { gold = vp.inventory.gold; } catch (Exception e) {}
                    try { hpMax = vp.nPoint.hpMax; } catch (Exception e) {}
                    try { hp = vp.nPoint.hp; } catch (Exception e) {}
                    try { dame = vp.nPoint.dame; } catch (Exception e) {}
                    try { mapId = vp.zone != null ? vp.zone.map.mapId : -1; } catch (Exception e) { mapId = -1; }
                    String state = vp.state != null ? vp.state.name() : "NULL";

                    StringBuilder info = new StringBuilder();
                    info.append("{")
                        .append("\"id\":").append(vp.id).append(",")
                        .append("\"name\":\"").append(jsonEscape(vp.name)).append("\",")
                        .append("\"gender\":").append(gender).append(",")
                        .append("\"head\":").append(headId).append(",")
                        .append("\"power\":").append(power).append(",")
                        .append("\"gold\":").append(gold).append(",")
                        .append("\"hp\":").append(hp).append(",")
                        .append("\"hp_max\":").append(hpMax).append(",")
                        .append("\"dame\":").append(dame).append(",")
                        .append("\"state\":\"").append(state).append("\",")
                        .append("\"map_id\":").append(mapId)
                        .append("}");

                    StringBuilder body = new StringBuilder();
                    try {
                        for (int i = 0; i < vp.inventory.itemsBody.size(); i++) {
                            models.Item.Item it = vp.inventory.itemsBody.get(i);
                            if (it == null || !it.isNotNullItem()) continue;
                            if (body.length() > 0) body.append(",");
                            body.append("{\"slot\":").append(i)
                                .append(",\"tempid\":").append(it.template.id)
                                .append(",\"qty\":").append(it.quantity)
                                .append(",\"name\":\"").append(jsonEscape(it.template.name)).append("\"")
                                .append(",\"icon\":").append(it.template.iconID)
                                .append("}");
                        }
                    } catch (Exception e) {}

                    StringBuilder bag = new StringBuilder();
                    try {
                        for (int i = 0; i < vp.inventory.itemsBag.size(); i++) {
                            models.Item.Item it = vp.inventory.itemsBag.get(i);
                            if (it == null || !it.isNotNullItem()) continue;
                            if (bag.length() > 0) bag.append(",");
                            bag.append("{\"index\":").append(i)
                                .append(",\"tempid\":").append(it.template.id)
                                .append(",\"qty\":").append(it.quantity)
                                .append(",\"name\":\"").append(jsonEscape(it.template.name)).append("\"")
                                .append(",\"icon\":").append(it.template.iconID)
                                .append("}");
                        }
                    } catch (Exception e) {}

                    sendResponse(exchange, "{\"status\": \"success\", \"info\": " + info.toString()
                        + ", \"items_body\": [" + body.toString() + "]"
                        + ", \"items_bag\": [" + bag.toString() + "]}");
                }
            });

            // S?a th�ng tin c? b?n bot (t�n / s?c m?nh / v�ng)
            server.createContext("/api/vp_edit_info", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = -1;
                    try { id = Long.parseLong(query.getOrDefault("id", "-1")); } catch (Exception e) {}
                    nro.virtualplayer.VirtualPlayer vp = nro.virtualplayer.VirtualPlayerManager.gI().getBot(id);
                    if (vp == null) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y bot!\"}");
                        return;
                    }

                    int changed = 0;
                    if (query.containsKey("name") && !query.get("name").trim().isEmpty()) {
                        String newName = query.get("name").trim().replace("\\", "").replace("\"", "").replace("'", "");
                        if (!newName.isEmpty() && newName.length() <= 24) {
                            vp.name = newName;
                            changed++;
                        }
                    }
                    if (query.containsKey("power")) {
                        try {
                            long pw = Long.parseLong(query.get("power"));
                            if (pw >= 0 && vp.nPoint != null) {
                                vp.nPoint.power = pw;
                                changed++;
                            }
                        } catch (Exception e) {}
                    }
                    if (query.containsKey("gold")) {
                        try {
                            long g = Long.parseLong(query.get("gold"));
                            if (g >= 0 && vp.inventory != null) {
                                vp.inventory.gold = g;
                                changed++;
                            }
                        } catch (Exception e) {}
                    }
                    sendResponse(exchange, changed > 0
                        ? "{\"status\": \"success\", \"msg\": \"Đ� c?p nh?t " + changed + " th�ng tin!\"}"
                        : "{\"status\": \"error\", \"msg\": \"Kh�ng c� g� thay ??i!\"}");
                }
            });

            // X�a item khỏi body/bag (slot -> � tr?ng)
            server.createContext("/api/vp_item_del", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = -1;
                    try { id = Long.parseLong(query.getOrDefault("id", "-1")); } catch (Exception e) {}
                    nro.virtualplayer.VirtualPlayer vp = nro.virtualplayer.VirtualPlayerManager.gI().getBot(id);
                    if (vp == null) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y bot!\"}");
                        return;
                    }
                    int slot = -1;
                    try { slot = Integer.parseInt(query.getOrDefault("slot", "-1")); } catch (Exception e) {}
                    String type = query.containsKey("type") ? query.get("type") : "";
                    try {
                        java.util.List<models.Item.Item> list =
                            type.equals("body") ? vp.inventory.itemsBody : vp.inventory.itemsBag;
                        if (slot < 0 || slot >= list.size()) {
                            sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Slot kh�ng h?p l?!\"}");
                            return;
                        }
                        list.set(slot, models.Item.ItemService.gI().createItemNull());
                        if (type.equals("body")) refreshOutfit(vp);
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� x�a v?t ph?m!\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"L?i: "
                            + jsonEscape(e.getMessage() == null ? "?" : e.getMessage()) + "\"}");
                    }
                }
            });

            // Th�m item v�o body/bag
            server.createContext("/api/vp_item_add", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = -1;
                    try { id = Long.parseLong(query.getOrDefault("id", "-1")); } catch (Exception e) {}
                    nro.virtualplayer.VirtualPlayer vp = nro.virtualplayer.VirtualPlayerManager.gI().getBot(id);
                    if (vp == null) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y bot!\"}");
                        return;
                    }
                    int tempId = -1, qty = 1;
                    try { tempId = Integer.parseInt(query.getOrDefault("tempid", "-1")); } catch (Exception e) {}
                    try { qty = Integer.parseInt(query.getOrDefault("qty", "1")); } catch (Exception e) {}
                    if (qty < 1) qty = 1;
                    if (qty > 9999) qty = 9999;

                    nro.template.ItemTemplate tpl = null;
                    try { tpl = models.Item.ItemService.gI().getTemplate(tempId); } catch (Exception e) {}
                    if (tpl == null) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng c� template ID " + tempId + "!\"}");
                        return;
                    }
                    String type = query.containsKey("type") ? query.get("type") : "bag";
                    try {
                        models.Item.Item it = models.Item.ItemService.gI().createNewItem((short) tempId, qty);
                        if (it == null) {
                            sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t?o ???c v?t ph?m!\"}");
                            return;
                        }
                        if (type.equals("body")) {
                            int freeSlot = -1;
                            for (int i = 0; i < vp.inventory.itemsBody.size(); i++) {
                                models.Item.Item cur = vp.inventory.itemsBody.get(i);
                                if (cur == null || !cur.isNotNullItem()) { freeSlot = i; break; }
                            }
                            if (freeSlot < 0) {
                                sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Trang b? ?� ??y!\"}");
                                return;
                            }
                            vp.inventory.itemsBody.set(freeSlot, it);
                            refreshOutfit(vp);
                        } else {
                            nro.inventory.InventoryService.gI().addItemBag(vp, it);
                        }
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� th�m ["
                            + jsonEscape(tpl.name) + "] x" + qty + "!\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"L?i: "
                            + jsonEscape(e.getMessage() == null ? "?" : e.getMessage()) + "\"}");
                    }
                }
            });

            // M?c ?? t? t�i v�o body
            server.createContext("/api/vp_item_equip", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = -1;
                    try { id = Long.parseLong(query.getOrDefault("id", "-1")); } catch (Exception e) {}
                    nro.virtualplayer.VirtualPlayer vp = nro.virtualplayer.VirtualPlayerManager.gI().getBot(id);
                    if (vp == null) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y bot!\"}");
                        return;
                    }
                    int idx = -1;
                    try { idx = Integer.parseInt(query.getOrDefault("bag_index", "-1")); } catch (Exception e) {}
                    try {
                        if (idx < 0 || idx >= vp.inventory.itemsBag.size()) {
                            sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"� kh�ng h?p l?!\"}");
                            return;
                        }
                        models.Item.Item it = vp.inventory.itemsBag.get(idx);
                        if (it == null || !it.isNotNullItem()) {
                            sendResponse(exchange, "{\"status\": \"error\", \"msg\": � tr?ng!\"}");
                            return;
                        }
                        nro.inventory.InventoryService.gI().itemBagToBody(vp, idx);
                        refreshOutfit(vp);
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": Đ� m?c ["
                            + jsonEscape(it.template.name) + "]!\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": L?i: "
                            + jsonEscape(e.getMessage() == null ? "?" : e.getMessage()) + "\"}");
                    }
                }
            });

            // === VP Config Set (runtime + save to file) ===
            server.createContext("/api/vp_config_set", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    String key = query.getOrDefault("key", "").toLowerCase();
                    String val = query.getOrDefault("val", "");
                    nro.virtualplayer.VirtualConfig cfg = nro.virtualplayer.VirtualConfig.gI();
                    try {
                        switch (key) {
                            case "exp_rate": cfg.expRate = Math.max(0f, Math.min(1f, Float.parseFloat(val))); break;
                            case "gold_rate": cfg.goldRate = Math.max(0f, Math.min(1f, Float.parseFloat(val))); break;
                            case "chat_rate": cfg.chatRate = Math.max(0f, Math.min(1f, Float.parseFloat(val))); break;
                            case "map_change_rate": cfg.mapChangeRate = Math.max(0f, Math.min(1f, Float.parseFloat(val))); break;
                            case "gift_rate": cfg.giftRate = Math.max(0f, Math.min(1f, Float.parseFloat(val))); break;
                            case "afk_rate": cfg.afkRate = Math.max(0f, Math.min(1f, Float.parseFloat(val))); break;
                            case "player_protection": cfg.playerProtection = "1".equals(val) || "true".equalsIgnoreCase(val); break;
                            case "presence_per_player": cfg.presencePerPlayer = Math.max(0, Math.min(50, Math.round(Float.parseFloat(val)))); break;
                            case "presence_visit_seconds": cfg.presenceVisitSeconds = Math.max(30, Math.min(3600, Math.round(Float.parseFloat(val)))); break;
                            default:
                                sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Key khong hop le! Keys: exp_rate, gold_rate, chat_rate, map_change_rate, gift_rate, afk_rate, player_protection, presence_per_player, presence_visit_seconds\"}");
                                return;
                        }
                        saveVpConfig(cfg);
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Da cap nhat " + jsonEscape(key) + " = " + jsonEscape(val) + "\"}");
                    } catch (NumberFormatException e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Gia tri khong hop le!\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Loi: " + e.getMessage() + "\"}");
                    }
                }
            });

            // === VP Debug Summary ===
            server.createContext("/api/vp_debug", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    try {
                        java.util.List<nro.virtualplayer.VirtualPlayer> bots = nro.virtualplayer.VirtualPlayerManager.gI().getBots();
                        java.util.List<nro.virtualplayer.VirtualPlayer> online = nro.virtualplayer.VirtualPlayerManager.gI().getOnlineBots();
                        StringBuilder sb = new StringBuilder();
                        sb.append("Total: ").append(bots.size()).append(", Online: ").append(online.size()).append("\n");
                        for (nro.virtualplayer.VirtualPlayer vp : bots) {
                            int mapId = -1;
                            try { mapId = vp.zone != null ? vp.zone.map.mapId : -1; } catch (Exception e) {}
                            String state = vp.state != null ? vp.state.name() : "?";
                            sb.append("[").append(vp.id).append("] ").append(vp.name)
                              .append(" | power=").append(vp.nPoint.power)
                              .append(" | map=").append(mapId)
                              .append(" | state=").append(state)
                              .append(" | online=").append(vp.isActive())
                              .append("\n");
                        }
                        sendResponse(exchange, "{\"status\": \"success\", \"debug\": \"" + jsonEscape(sb.toString()) + "\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Loi: " + e.getMessage() + "\"}");
                    }
                }
            });

            // === VP Teleport ===
            server.createContext("/api/vp_teleport", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = -1;
                    int mapId = 0, x = 300, y = 300;
                    try { id = Long.parseLong(query.getOrDefault("id", "-1")); } catch (Exception e) {}
                    try { mapId = Integer.parseInt(query.getOrDefault("map", "0")); } catch (Exception e) {}
                    try { x = Integer.parseInt(query.getOrDefault("x", "300")); } catch (Exception e) {}
                    try { y = Integer.parseInt(query.getOrDefault("y", "300")); } catch (Exception e) {}
                    nro.virtualplayer.VirtualPlayer vp = nro.virtualplayer.VirtualPlayerManager.gI().getBot(id);
                    if (vp == null) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Khong tim thay bot!\"}");
                        return;
                    }
                    try {
                        vp.joinMap(mapId, x, y);
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Da dich chuyen " + jsonEscape(vp.name) + " den map " + mapId + "\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"" + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // === VP Regear ===
            server.createContext("/api/vp_regear", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = -1;
                    try { id = Long.parseLong(query.getOrDefault("id", "-1")); } catch (Exception e) {}
                    nro.virtualplayer.VirtualPlayer vp = nro.virtualplayer.VirtualPlayerManager.gI().getBot(id);
                    if (vp == null) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Khong tim thay bot!\"}");
                        return;
                    }
                    try {
                        vp.getEquipment().giveStarterGear();
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Da trang bi lai cho " + jsonEscape(vp.name) + "\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"" + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // === VP Add Gold (proper economy path) ===
            server.createContext("/api/vp_add_gold", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = -1;
                    long amount = 0;
                    try { id = Long.parseLong(query.getOrDefault("id", "-1")); } catch (Exception e) {}
                    try { amount = Long.parseLong(query.getOrDefault("amount", "0")); } catch (Exception e) {}
                    nro.virtualplayer.VirtualPlayer vp = nro.virtualplayer.VirtualPlayerManager.gI().getBot(id);
                    if (vp == null) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Khong tim thay bot!\"}");
                        return;
                    }
                    try {
                        new nro.virtualplayer.VirtualEconomy(vp).addGold(amount);
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Da them " + amount + " vang cho " + jsonEscape(vp.name) + "\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"" + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // === VP Quest ===
            server.createContext("/api/vp_quest", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = -1;
                    try { id = Long.parseLong(query.getOrDefault("id", "-1")); } catch (Exception e) {}
                    nro.virtualplayer.VirtualPlayer vp = nro.virtualplayer.VirtualPlayerManager.gI().getBot(id);
                    if (vp == null) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Khong tim thay bot!\"}");
                        return;
                    }
                    try {
                        nro.virtualplayer.VirtualQuest quest = new nro.virtualplayer.VirtualQuest(vp);
                        String desc = quest.describeCurrentTask();
                        int objMap = quest.getObjectiveMap();
                        boolean done = quest.isSubTaskDone();
                        sendResponse(exchange, "{\"status\": \"success\", \"task\": \"" + jsonEscape(desc)
                            + "\", \"objective_map\":" + objMap + ", \"subtask_done\":" + done + "}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"" + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // === VP Profile ===
            server.createContext("/api/vp_profile", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = -1;
                    try { id = Long.parseLong(query.getOrDefault("id", "-1")); } catch (Exception e) {}
                    nro.virtualplayer.VirtualPlayer vp = nro.virtualplayer.VirtualPlayerManager.gI().getBot(id);
                    if (vp == null) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Khong tim thay bot!\"}");
                        return;
                    }
                    try {
                        nro.virtualplayer.core.VirtualProfile p = vp.profile;
                        StringBuilder pers = new StringBuilder("[");
                        boolean first = true;
                        for (nro.virtualplayer.core.VirtualPersonality pr : p.getPersonalities()) {
                            if (!first) pers.append(",");
                            pers.append("\"").append(pr.name()).append("\"");
                            first = false;
                        }
                        pers.append("]");
                        sendResponse(exchange, "{\"status\": \"success\""
                            + ", \"name\":\"" + jsonEscape(p.getName()) + "\""
                            + ", \"personalities\":" + pers.toString()
                            + ", \"talkativeness\":" + p.getTalkativeness()
                            + ", \"risk_tolerance\":" + p.getRiskTolerance()
                            + ", \"helpfulness\":" + p.getHelpfulness()
                            + ", \"competitiveness\":" + p.getCompetitiveness()
                            + ", \"laziness\":" + p.getLaziness()
                            + ", \"greed\":" + p.getGreed()
                            + "}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"" + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // === VP Needs ===
            server.createContext("/api/vp_needs", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = -1;
                    try { id = Long.parseLong(query.getOrDefault("id", "-1")); } catch (Exception e) {}
                    nro.virtualplayer.VirtualPlayer vp = nro.virtualplayer.VirtualPlayerManager.gI().getBot(id);
                    if (vp == null) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Khong tim thay bot!\"}");
                        return;
                    }
                    try {
                        nro.virtualplayer.core.VirtualNeeds n = vp.needs;
                        sendResponse(exchange, "{\"status\": \"success\""
                            + ", \"hp\":" + n.getHpNeed()
                            + ", \"mp\":" + n.getMpNeed()
                            + ", \"exp\":" + n.getExpNeed()
                            + ", \"gold\":" + n.getGoldNeed()
                            + ", \"item\":" + n.getItemNeed()
                            + ", \"quest\":" + n.getQuestNeed()
                            + ", \"social\":" + n.getSocialNeed()
                            + ", \"rest\":" + n.getRestNeed()
                            + ", \"safety\":" + n.getSafetyNeed()
                            + ", \"explore\":" + n.getExploreNeed()
                            + "}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"" + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // === VP Goals ===
            server.createContext("/api/vp_goals", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = -1;
                    try { id = Long.parseLong(query.getOrDefault("id", "-1")); } catch (Exception e) {}
                    nro.virtualplayer.VirtualPlayer vp = nro.virtualplayer.VirtualPlayerManager.gI().getBot(id);
                    if (vp == null) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Khong tim thay bot!\"}");
                        return;
                    }
                    try {
                        nro.virtualplayer.core.VirtualGoals g = vp.goals;
                        sendResponse(exchange, "{\"status\": \"success\""
                            + ", \"long_term\":\"" + (g.getLongTerm() != null ? g.getLongTerm().name() : "NONE") + "\""
                            + ", \"short_term\":\"" + (g.getShortTerm() != null ? g.getShortTerm().name() : "NONE") + "\""
                            + "}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"" + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });
            // ================= END VIRTUAL PLAYER =================

            server.createContext("/api/boss_reset", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    BossManager.gI().resetAllBosses();
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� Reset to�n b? Boss!\"}");
                }
            });

            server.createContext("/api/boss_respawn_resting", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    int n = BossManager.gI().respawnAllRestingBosses();
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� h?i sinh " + n + " Boss ?ang ngh?!\"}");
                }
            });

            server.createContext("/api/boss_list", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    StringBuilder sb = new StringBuilder("[");
                    try {
                        java.util.Map<String, Integer> idMap = new HashMap<>();
                        for (Field f : BossID.class.getFields()) {
                            if (Modifier.isStatic(f.getModifiers()) && f.getType() == int.class) {
                                idMap.put(f.getName(), f.getInt(null));
                            }
                        }
                        java.util.Set<String> summonKeys = new java.util.HashSet<>();
                        try {
                            String src = new String(java.nio.file.Files.readAllBytes(
                                    java.nio.file.Paths.get("src/QuanLiBoss/Manager/BossManager.java")), "UTF-8");
                            java.util.regex.Matcher m = java.util.regex.Pattern
                                    .compile("createBoss\\s*\\(\\s*BossID\\.([A-Z0-9_]+)").matcher(src);
                            while (m.find()) summonKeys.add(m.group(1));
                        } catch (Exception e) {}
                        boolean filter = !summonKeys.isEmpty();
                        java.util.Set<Integer> seenIds = new java.util.HashSet<>();
                        boolean first = true;
                        if (filter) {
                            for (Field df : QuanLiBoss.BossesData.class.getFields()) {
                                if (!Modifier.isStatic(df.getModifiers()) || df.getType() != QuanLiBoss.BossData.class) continue;
                                if (!summonKeys.contains(df.getName())) continue;
                                Integer id = idMap.get(df.getName());
                                if (id == null || !seenIds.add(id)) continue;
                                String disp = null;
                                try {
                                    disp = ((QuanLiBoss.BossData) df.get(null)).getName();
                                } catch (Exception e) {}
                                if (disp == null || disp.trim().isEmpty()) {
                                    disp = df.getName().replace("_", " ").toLowerCase();
                                    disp = Character.toUpperCase(disp.charAt(0)) + disp.substring(1);
                                }
                                if (!first) sb.append(",");
                                sb.append("{\"id\":").append(id).append(",\"name\":\"").append(jsonEscape(disp)).append("\"}");
                                first = false;
                            }
                        }
                        if (first) {
                            for (Field field : BossID.class.getFields()) {
                                if (Modifier.isStatic(field.getModifiers()) && field.getType() == int.class) {
                                    String name = field.getName().replace("_", " ").toLowerCase();
                                    name = Character.toUpperCase(name.charAt(0)) + name.substring(1);
                                    int id = field.getInt(null);
                                    if (!first) sb.append(",");
                                    sb.append("{\"id\":").append(id).append(",\"name\":\"").append(jsonEscape(name)).append("\"}");
                                    first = false;
                                }
                            }
                        }
                    } catch (Exception e) {}
                    sb.append("]");
                    sendResponse(exchange, sb.toString());
                }
            });

            server.createContext("/api/consign_list", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    StringBuilder sb = new StringBuilder("[");
                    try {
                        List<nro.consignmentstore.ConsignItem> items = nro.consignmentstore.ConsignShopManager.gI().listItem;
                        boolean first = true;
                        int shown = 0;
                        for (int i = items.size() - 1; i >= 0 && shown < 100; i--) {
                            nro.consignmentstore.ConsignItem ci = items.get(i);
                            if (ci == null) continue;
                            String iname = "?";
                            try {
                                if (Manager.ITEM_TEMPLATES != null && ci.itemId >= 0 && ci.itemId < Manager.ITEM_TEMPLATES.size()
                                        && Manager.ITEM_TEMPLATES.get((int) ci.itemId) != null) {
                                    iname = Manager.ITEM_TEMPLATES.get((int) ci.itemId).name;
                                }
                            } catch (Exception e) {}
                            String seller = "#" + ci.player_sell;
                            try {
                                Player sp = Client.gI().getPlayerByID(ci.player_sell);
                                if (sp != null) seller = sp.name;
                            } catch (Exception e) {}
                            boolean botSeller = false;
                            try {
                                nro.virtualplayer.VirtualPlayer botSellerVp = nro.virtualplayer.VirtualPlayerManager.gI().getBotByIntId(ci.player_sell);
                                if (botSellerVp != null) {
                                    botSeller = true;
                                    seller = botSellerVp.name + " (Bot)";
                                }
                            } catch (Exception e) {}
                            if (!first) sb.append(",");
                            first = false;
                            shown++;
                            sb.append("{\"id\":").append(ci.id)
                              .append(",\"item_id\":").append(ci.itemId)
                              .append(",\"name\":\"").append(jsonEscape(iname)).append("\"")
                              .append(",\"seller\":\"").append(jsonEscape(seller)).append("\"")
                              .append(",\"is_bot\":").append(botSeller)
                              .append(",\"gold\":").append(ci.goldSell)
                              .append(",\"gem\":").append(ci.gemSell)
                              .append(",\"qty\":").append(ci.quantity)
                              .append(",\"sold\":").append(ci.isBuy).append("}");
                        }
                    } catch (Exception e) {}
                    sb.append("]");
                    sendResponse(exchange, sb.toString());
                }
            });

            // ===== C?u h�nh t�nh n?ng port hashirama =====
            server.createContext("/api/feature_data", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    StringBuilder sb = new StringBuilder("{");
                    String[][] tables = {
                        {"kham_ngoc", "SELECT id, options FROM kham_ngoc"},
                        {"phong_thi_nghiem", "SELECT id, name_tab, name_binh, thoi_gian, item_nhan FROM phong_thi_nghiem"},
                        {"ruong_suu_tam", "SELECT id, type, id_item, option_id, param, name FROM ruong_suu_tam"},
                        {"moc_vong_quay", "SELECT id, max_value, item_id, quantity FROM moc_vong_quay"},
                        {"tambao_items", "SELECT id, key_item_id, item_id, quantity, tile_trung_thuong FROM tambao_items"}
                    };
                    try {
                        jbcd.CrisResultSet rs;
                        boolean firstTab = true;
                        for (String[] t : tables) {
                            if (!firstTab) sb.append(",");
                            firstTab = false;
                            sb.append("\"").append(t[0]).append("\":[");
                            try {
                                rs = jbcd.ConnectDB.executeQuery(t[1]);
                                int cols = 0;
                                java.util.List<String> colNames = new java.util.ArrayList<>();
                                for (String cn : new String[]{"id", "options", "name_tab", "name_binh", "thoi_gian",
                                        "item_nhan", "type", "id_item", "option_id", "param", "name",
                                        "max_value", "item_id2", "quantity", "key_item_id", "tile_trung_thuong"}) {
                                    colNames.add(cn);
                                }
                                boolean firstRow = true;
                                while (rs.next()) {
                                    if (!firstRow) sb.append(",");
                                    firstRow = false;
                                    sb.append("{");
                                    boolean firstCol = true;
                                    for (String cn : colNames) {
                                        try {
                                            Object v = rs.getObject(cn);
                                            if (v == null) continue;
                                            if (!firstCol) sb.append(",");
                                            firstCol = false;
                                            String key = cn.equals("item_id2") ? "item_id" : cn;
                                            String val = String.valueOf(v);
                                            if (val.length() > 300) {
                                                val = val.substring(0, 300) + "...";
                                            }
                                            // Escape SAU C�NG ?? kh�ng c?t h?ng chu?i escape
                                            val = val.replace("\\", "\\\\").replace("\"", "\\\"");
                                            val = val.replaceAll("[\\x00-\\x1f]", " ");
                                            sb.append("\"").append(key).append("\":\"").append(val).append("\"");
                                        } catch (Exception e) {}
                                    }
                                    sb.append("}");
                                }
                            } catch (Exception e) {}
                            sb.append("]");
                        }
                        // Th?ng k� ng??i d�ng
                        try {
                            rs = jbcd.ConnectDB.executeQuery(
                                "SELECT (SELECT COUNT(*) FROM player WHERE active_kham_ngoc=1) kn_active,"
                                + " (SELECT COUNT(*) FROM player WHERE active_ruong_suu_tam=1) rst_active,"
                                + " (SELECT COUNT(*) FROM player WHERE dan_duoc <> '[0,0,0]') danduoc_users");
                            if (rs.next()) {
                                sb.append(",\"stats\":{\"kham_ngoc_active\":").append(rs.getInt(1))
                                  .append(",\"ruong_suu_tam_active\":").append(rs.getInt(2))
                                  .append(",\"dan_duoc_users\":").append(rs.getInt(3)).append("}");
                            }
                        } catch (Exception e) {}
                    } catch (Exception e) {}
                    sb.append("}");
                    sendResponse(exchange, sb.toString());
                }
            });

            server.createContext("/api/boss_summon", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    int bossId = Integer.MIN_VALUE;
                    if (query.containsKey("val")) {
                        try {
                            bossId = Integer.parseInt(query.get("val"));
                        } catch (Exception e) {}
                    }
                    if (bossId == Integer.MIN_VALUE) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thi?u ID Boss!\"}");
                        return;
                    }
                    try {
                        Boss boss = BossManager.gI().createBoss(bossId);
                        if (boss == null) {
                            sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng th? t?o Boss ID: " + bossId + "\"}");
                            return;
                        }
                        boss.changeStatus(BossStatus.RESPAWN);
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� tri?u h?i Boss ID: " + bossId + "!\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"L?i khi tri?u h?i Boss: " + e.getMessage() + "\"}");
                    }
                }
            });


            server.createContext("/api/exp_change", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    if (query.containsKey("val")) {
                        try {
                            int val = Integer.parseInt(query.get("val"));
                            if (val < 1) val = 1;
                            Manager.RATE_EXP_SERVER = val;
                            sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� ??i EXP th�nh x" + Manager.RATE_EXP_SERVER + "!\"}");
                        } catch(Exception e) {
                            sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Gi� tr? kh�ng h?p l?!\"}");
                        }
                    } else {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thi?u gi� tr?!\"}");
                    }
                }
            });

            server.createContext("/api/kick_all", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Client.gI().close();
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� kick to�n b? ng?ời ch?i!\"}");
                }
            });

            server.createContext("/api/save_data", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    new Thread(() -> {
                        try {
                            for (nro.player.Player player : Client.gI().getPlayers()) {
                                if (player != null && !player.isBot) {
                                    jbcd.dao.PlayerDAO.updatePlayer(player);
                                }
                            }
                        } catch(Exception e) {}
                    }).start();
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� l?u d? li?u ng?ời ch?i th�nh c�ng!\"}");
                }
            });

            server.createContext("/api/update_shop", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Manager.gI().updateShop();
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� C?p nh?t Shop!\"}");
                }
            });

            server.createContext("/api/update_top", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    TopServer.LoadingTop();
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� C?p nh?t B?ng x?p h?ng (TOP)!\"}");
                }
            });

            // ==================== EVENT ====================
            server.createContext("/api/event_list", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    String[] names = {
                        "Kh�ng c� s? ki?n", "S? ki?n T?t", "S? ki?n Trung Thu", "S? ki?n Halloween",
                        "S? ki?n Noel", "S? ki?n Vu Lan", "S? ki?n Qu?c t? Ph? n?", "S? ki?n H�ng V??ng",
                        "S? ki?n Black Friday", "S? ki?n Valentine", "S? ki?n 20/10", "S? ki?n N?p Th?"
                    };
                    boolean[] states = eventStates();
                    StringBuilder sb = new StringBuilder("[");
                    for (int i = 0; i < names.length; i++) {
                        if (i > 0) sb.append(",");
                        sb.append("{\"id\":").append(i)
                          .append(",\"name\":\"").append(jsonEscape(names[i])).append("\"")
                          .append(",\"active\":").append(states[i]).append("}");
                    }
                    sb.append("]");
                    sendResponse(exchange, sb.toString());
                }
            });

            server.createContext("/api/event_set", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    int eventId = 0;
                    if (query.containsKey("val")) {
                        try {
                            eventId = Integer.parseInt(query.get("val"));
                        } catch (Exception e) {}
                    }
                    try {
                        event.EventManager.gI().setCurrentEvent(eventId);
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": " + jsonEscape("Da ap dung su kien!") + "}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": " + jsonEscape(String.valueOf(e.getMessage())) + "}");
                    }
                }
            });

            server.createContext("/api/event_toggle", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    int eventId = -1;
                    boolean on = true;
                    if (query.containsKey("id")) {
                        try { eventId = Integer.parseInt(query.get("id")); } catch (Exception e) {}
                    }
                    if (query.containsKey("val")) {
                        on = "1".equals(query.get("val")) || "true".equalsIgnoreCase(query.get("val"));
                    }
                    if (eventId < 0 || eventId > 11) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": " + jsonEscape("ID su kien khong hop le (0-11)!") + "}");
                        return;
                    }
                    try {
                        event.EventManager.gI().toggleEvent(eventId, on);
                        String state = event.EventManager.isEventActive(eventId) ? "BAT" : "TAT";
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": " + jsonEscape("Su kien #" + eventId + " da " + state + "!") + "}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": " + jsonEscape(String.valueOf(e.getMessage())) + "}");
                    }
                }
            });

            // ==================== PLAYER ====================
            server.createContext("/api/player_list", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    List<Player> players = Client.gI().getPlayers();
                    StringBuilder sb = new StringBuilder("[");
                    boolean first = true;
                    for (Player p : players) {
                        if (p == null) continue;
                        if (!first) sb.append(",");
                        sb.append("{\"id\":").append(p.id)
                          .append(",\"name\":\"").append(jsonEscape(p.name == null ? "" : p.name)).append("\"")
                          .append(",\"power\":").append(p.nPoint != null ? p.nPoint.power : 0)
                          .append(",\"isBot\":").append(p.isBot).append("}");
                        first = false;
                    }
                    sb.append("]");
                    sendResponse(exchange, sb.toString());
                }
            });

            server.createContext("/api/player_kick", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    String name = query.get("name");
                    if (name == null || name.isEmpty()) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thi?u t�n ng?ời ch?i!\"}");
                        return;
                    }
                    Player p = Client.gI().getPlayerByName(name);
                    if (p == null) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y ng?ời ch?i ?ang online: " + name + "\"}");
                        return;
                    }
                    Client.gI().kickSession(p.getSession());
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� kick ng?ời ch?i " + name + "!\"}");
                }
            });

            server.createContext("/api/player_ban", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    String name = query.get("name");
                    if (name == null || name.isEmpty()) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thi?u t�n ng?ời ch?i!\"}");
                        return;
                    }
                    Player p = Client.gI().getPlayerByName(name);
                    if (p == null) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y ng?ời ch?i ?ang online: " + name + "\"}");
                        return;
                    }
                    PlayerService.gI().KhoaTaiKhoan(p);
                    if (p.iDMark != null) {
                        p.iDMark.setBan(true);
                        p.iDMark.setLastTimeBan(System.currentTimeMillis());
                    }
                    Service.gI().sendThongBao(p, "T�i kho?n c?a b?n ?� b? kh�a b?i Admin!");
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� kh�a t�i kho?n " + name + "!\"}");
                }
            });

            server.createContext("/api/player_buff_vnd", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    String name = query.get("name");
                    int vnd = 0;
                    if (query.containsKey("vnd")) {
                        try { vnd = Integer.parseInt(query.get("vnd")); } catch (Exception e) {}
                    }
                    if (name == null || name.isEmpty() || vnd == 0) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thi?u t�n ho?c s? VNĐ!\"}");
                        return;
                    }
                    Player p = Client.gI().getPlayerByName(name);
                    if (p == null) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y ng?ời ch?i ?ang online: " + name + "\"}");
                        return;
                    }
                    boolean ok = DatabaseUpdater.addVND_byPlayer(p, vnd);
                    if (ok && p.inventory != null) {
                        p.inventory.addExpVip(vnd / 100);
                        Service.gI().sendVipExp(p);
                    }
                    Service.gI().sendThongBao(p, "Admin ?� c?ng " + vnd + " VNĐ cho b?n!");
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� c?ng " + vnd + " VNĐ cho " + name + "!\"}");
                }
            });

            server.createContext("/api/player_give_item", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    String name = query.get("name");
                    int itemId = 0, qty = 1, optId = 0, optParam = 0;
                    if (query.containsKey("item")) { try { itemId = Integer.parseInt(query.get("item")); } catch (Exception e) {} }
                    if (query.containsKey("qty")) { try { qty = Integer.parseInt(query.get("qty")); } catch (Exception e) {} }
                    if (query.containsKey("opt_id")) { try { optId = Integer.parseInt(query.get("opt_id")); } catch (Exception e) {} }
                    if (query.containsKey("opt_param")) { try { optParam = Integer.parseInt(query.get("opt_param")); } catch (Exception e) {} }
                    if (name == null || name.isEmpty() || itemId <= 0) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thi?u t�n ho?c ID v?t ph?m!\"}");
                        return;
                    }
                    Player p = Client.gI().getPlayerByName(name);
                    if (p == null) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y ng?ời ch?i ?ang online: " + name + "\"}");
                        return;
                    }
                    try {
                        Item newItem = ItemService.gI().createNewItem((short) itemId);
                        if (newItem == null) {
                            sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y Item ID: " + itemId + "\"}");
                            return;
                        }
                        newItem.quantity = qty;
                        if (optId > 0) newItem.itemOptions.add(new ItemOption(optId, optParam));
                        InventoryService.gI().addItemBag(p, newItem);
                        InventoryService.gI().sendItemBag(p);
                        Service.gI().sendThongBao(p, "Admin ?� g?i v?t ph?m cho b?n!");
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� g?i " + qty + " x Item " + itemId + " cho " + name + "!\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"L?i: " + e.getMessage() + "\"}");
                    }
                }
            });

            server.createContext("/api/player_set_task", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    String name = query.get("name");
                    int taskId = 0, subtask = 0;
                    if (query.containsKey("task")) { try { taskId = Integer.parseInt(query.get("task")); } catch (Exception e) {} }
                    if (query.containsKey("subtask")) { try { subtask = Integer.parseInt(query.get("subtask")); } catch (Exception e) {} }
                    if (name == null || name.isEmpty()) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thi?u t�n ng?ời ch?i!\"}");
                        return;
                    }
                    Player p = Client.gI().getPlayerByName(name);
                    if (p == null) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y ng?ời ch?i ?ang online: " + name + "\"}");
                        return;
                    }
                    try {
                        TaskMain newTask = TaskService.gI().getTaskMainById(p, taskId);
                        if (newTask == null) {
                            sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y nhi?m v? ID: " + taskId + "\"}");
                            return;
                        }
                        newTask.index = (byte) subtask;
                        if (newTask.subTasks != null && subtask < newTask.subTasks.size()) {
                            newTask.subTasks.get(subtask).count = 0;
                        }
                        p.playerTask.taskMain = newTask;
                        TaskService.gI().sendTaskMain(p);
                        Service.gI().sendThongBao(p, "Admin ?� chuy?n nhi?m v? c?a b?n!");
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� set nhi?m v? cho " + name + "!\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"L?i: " + e.getMessage() + "\"}");
                    }
                }
            });

            server.createContext("/api/player_mtv", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    String name = query.get("name");
                    if (name == null || name.isEmpty()) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thi?u t�n ng?ời ch?i!\"}");
                        return;
                    }
                    Player p = Client.gI().getPlayerByName(name);
                    if (p == null) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y ng?ời ch?i ?ang online: " + name + "\"}");
                        return;
                    }
                    PlayerService.gI().MoThanhVienPlayer(p);
                    Service.gI().sendThongBao(p, "B?n ?� ???c m? th�nh vi�n b?i Admin!");
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� m? th�nh vi�n cho " + name + "!\"}");
                }
            });

            // ================= QUAN LY NHAN VAT CHI TIET (DB + live) =================

            // Chi tiet nhan vat: doc DB + trang thai online
            server.createContext("/api/pl_detail", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = -1;
                    try { id = Long.parseLong(query.getOrDefault("id", "-1")); } catch (Exception e) {}
                    if (id < 0) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thi?u ID nh�n v?t!\"}");
                        return;
                    }
                    try {
                        jbcd.CrisResultSet rs = jbcd.ConnectDB.executeQuery(
                            "SELECT name, head, gender, data_point, data_inventory, items_body, items_bag, items_box, pet, data_task, data_side_task, data_clan_task, data_kol_task, dataBadges FROM player WHERE id=?", id);
                        if (!rs.next()) {
                            sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y nh�n v?t!\"}");
                            return;
                        }
                        Player online = null;
                        try { online = Client.gI().getPlayerByID(id); } catch (Exception e) {}
                        com.google.gson.JsonArray dp = jparseArr(rs.getString("data_point"));
                        com.google.gson.JsonArray inv = jparseArr(rs.getString("data_inventory"));
                        com.google.gson.JsonArray mainT = jparseArr(rs.getString("data_task"));
                        com.google.gson.JsonArray sideT = jparseArr(rs.getString("data_side_task"));
                        com.google.gson.JsonArray clanT = jparseArr(rs.getString("data_clan_task"));
                        com.google.gson.JsonArray kolT = jparseArr(rs.getString("data_kol_task"));

                        StringBuilder sb = new StringBuilder();
                        sb.append("{\"status\": \"success\",")
                          .append("\"online\": ").append(online != null).append(",")
                          .append("\"info\": {\"id\":").append(id)
                          .append(",\"name\":\"").append(jsonEscape(rs.getString("name"))).append("\"")
                          .append(",\"head\":").append(rs.getInt("head"))
                          .append(",\"gender\":").append(rs.getInt("gender")).append("},")
                          .append("\"power\":").append(jLongVal(jGet(dp, 1))).append(",")
                          .append("\"tiemnang\":").append(jLongVal(jGet(dp, 2))).append(",")
                          .append("\"hpg\":").append(jLongVal(jGet(dp, 5))).append(",")
                          .append("\"mpg\":").append(jLongVal(jGet(dp, 6))).append(",")
                          .append("\"dameg\":").append(jLongVal(jGet(dp, 7))).append(",")
                          .append("\"defg\":").append(jLongVal(jGet(dp, 8))).append(",")
                          .append("\"critg\":").append(jLongVal(jGet(dp, 9))).append(",")
                          .append("\"gold\":").append(jLongVal(jGet(inv, 0))).append(",")
                          .append("\"gem\":").append(jLongVal(jGet(inv, 1))).append(",")
                          .append("\"ruby\":").append(jLongVal(jGet(inv, 2))).append(",")
                          .append("\"items_body\": ").append(plItemsJson(rs.getString("items_body"))).append(",")
                          .append("\"items_bag\": ").append(plItemsJson(rs.getString("items_bag"))).append(",")
                          .append("\"items_box\": ").append(plItemsJson(rs.getString("items_box"))).append(",")
                          .append("\"pet\": ").append(plPetJson(rs.getString("pet"))).append(",")
                          .append("\"task_main\": [").append(jGet(mainT, 0)).append(",").append(jGet(mainT, 1))
                          .append(",").append(jGet(mainT, 2)).append("],")
                          .append("\"task_side\": {\"id\":").append(jGet(sideT, 0)).append(",\"count\":").append(jGet(sideT, 2))
                          .append(",\"max\":").append(jGet(sideT, 3)).append(",\"left\":").append(jGet(sideT, 4))
                          .append(",\"level\":").append(jGet(sideT, 5)).append("},")
                          .append("\"task_clan\": {\"id\":").append(jGet(clanT, 0)).append(",\"count\":").append(jGet(clanT, 2))
                          .append(",\"max\":").append(jGet(clanT, 3)).append(",\"left\":").append(jGet(clanT, 4))
                          .append(",\"level\":").append(jGet(clanT, 5)).append("},")
                          .append("\"task_kol\": {\"id\":").append(jGet(kolT, 0)).append(",\"count\":").append(jGet(kolT, 1)).append("},")
                          .append("\"badges\": ").append(plBadgesJson(rs.getString("dataBadges")))
                          .append("}");
                        sendResponse(exchange, sb.toString());
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"L?i: "
                            + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // Luu thong tin chung: ten/head/diem/tai san
            server.createContext("/api/pl_save_info", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = -1;
                    try { id = Long.parseLong(query.getOrDefault("id", "-1")); } catch (Exception e) {}
                    if (id < 0) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thi?u ID nh�n v?t!\"}");
                        return;
                    }
                    try {
                        jbcd.CrisResultSet rs = jbcd.ConnectDB.executeQuery(
                            "SELECT name, head, data_point, data_inventory FROM player WHERE id=?", id);
                        if (!rs.next()) {
                            sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y nh�n v?t!\"}");
                            return;
                        }
                        com.google.gson.JsonArray dp = jparseArr(rs.getString("data_point"));
                        com.google.gson.JsonArray inv = jparseArr(rs.getString("data_inventory"));
                        String oldName = rs.getString("name");
                        int oldHead = rs.getInt("head");

                        String newName = sanitizeName(query.getOrDefault("name", oldName));
                        if (newName.isEmpty()) newName = oldName;
                        if (!newName.equals(oldName)) {
                            jbcd.CrisResultSet rs2 = jbcd.ConnectDB.executeQuery(
                                "SELECT id FROM player WHERE name=? AND id<>?", newName, id);
                            if (rs2.next()) {
                                sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"T�n ?� t?n t?i!\"}");
                                return;
                            }
                        }
                        long fHead = qLongOr(query, "head", oldHead);
                        long fPower = qLongOr(query, "power", jLongVal(jGet(dp, 1)));
                        long fTiem = qLongOr(query, "tiemnang", jLongVal(jGet(dp, 2)));
                        long fHpg = qLongOr(query, "hpg", jLongVal(jGet(dp, 5)));
                        long fMpg = qLongOr(query, "mpg", jLongVal(jGet(dp, 6)));
                        long fDameg = qLongOr(query, "dameg", jLongVal(jGet(dp, 7)));
                        long fDefg = qLongOr(query, "defg", jLongVal(jGet(dp, 8)));
                        long fCritg = qLongOr(query, "critg", jLongVal(jGet(dp, 9)));
                        long fGold = qLongOr(query, "gold", jLongVal(jGet(inv, 0)));
                        long fGem = qLongOr(query, "gem", jLongVal(jGet(inv, 1)));
                        long fRuby = qLongOr(query, "ruby", jLongVal(jGet(inv, 2)));

                        jSetNum(dp, 1, fPower);
                        jSetNum(dp, 2, fTiem);
                        jSetNum(dp, 5, fHpg);
                        jSetNum(dp, 6, fMpg);
                        jSetNum(dp, 7, fDameg);
                        jSetNum(dp, 8, fDefg);
                        jSetNum(dp, 9, fCritg);
                        jSetNum(inv, 0, fGold);
                        jSetNum(inv, 1, fGem);
                        jSetNum(inv, 2, fRuby);

                        jbcd.ConnectDB.executeUpdate(
                            "UPDATE player SET name=?, head=?, data_point=?, data_inventory=? WHERE id=?",
                            newName, (int) fHead, dp.toString(), inv.toString(), id);

                        String note = "nh�n v?t offline - v�o game s? �p d?ng";
                        Player p = null;
                        try { p = Client.gI().getPlayerByID(id); } catch (Exception e) {}
                        if (p != null && p.nPoint != null && p.inventory != null) {
                            p.name = newName;
                            p.head = (short) fHead;
                            p.nPoint.power = fPower;
                            p.nPoint.tiemNang = fTiem;
                            p.nPoint.hpg = fHpg;
                            p.nPoint.mpg = fMpg;
                            p.nPoint.dameg = fDameg;
                            p.nPoint.defg = (int) fDefg;
                            p.nPoint.critg = (int) fCritg;
                            p.inventory.gold = fGold;
                            p.inventory.gem = (int) fGem;
                            p.inventory.ruby = (int) fRuby;
                            Service.gI().point(p);
                            Service.gI().sendMoney(p);
                            Service.gI().player(p);
                            note = "?� �p d?ng ngay v� ?ang online";
                        }
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"?� l?u! (" + note + ")\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"L?i: "
                            + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // Xoa vat pham khoi body/bag/box
            server.createContext("/api/pl_item_del", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = plPlayerId(query);
                    if (id < 0) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thi?u ID!\"}"); return; }
                    String type = query.getOrDefault("type", "bag");
                    String col = type.equals("body") ? "items_body" : (type.equals("box") ? "items_box" : "items_bag");
                    int slot = (int) qLongOr(query, "slot", -1);
                    if (slot < 0 || slot > 120) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Slot kh�ng h?p l?!\"}"); return; }
                    try {
                        jbcd.CrisResultSet rs = jbcd.ConnectDB.executeQuery(
                            "SELECT " + col + " FROM player WHERE id=?", id);
                        if (!rs.next()) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y nh�n v?t!\"}"); return; }
                        com.google.gson.JsonArray arr = jparseArr(rs.getString(col));
                        if (slot >= arr.size()) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Slot v??t qu� s?c ch?a!\"}"); return; }
                        arr.set(slot, new com.google.gson.JsonPrimitive(plItemElem(-1, 0, "[]", System.currentTimeMillis())));
                        jbcd.ConnectDB.executeUpdate("UPDATE player SET " + col + "=? WHERE id=?", arr.toString(), id);

                        String note = "v�o game s? �p d?ng";
                        Player p = null;
                        try { p = Client.gI().getPlayerByID(id); } catch (Exception e) {}
                        if (p != null && p.inventory != null) {
                            java.util.List<models.Item.Item> list = type.equals("body") ? p.inventory.itemsBody
                                : (type.equals("box") ? p.inventory.itemsBox : p.inventory.itemsBag);
                            if (slot < list.size()) {
                                list.set(slot, models.Item.ItemService.gI().createItemNull());
                                if (type.equals("body")) InventoryService.gI().sendItemBody(p);
                                else if (type.equals("box")) InventoryService.gI().sendItemBox(p);
                                else InventoryService.gI().sendItemBag(p);
                            }
                            note = "?� �p d?ng ngay v� ?ang online";
                        }
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"?� x�a v?t ph?m! (" + note + ")\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"L?i: "
                            + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // Them vat pham vao body/bag/box (vao o trong dau tien)
            server.createContext("/api/pl_item_add", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = plPlayerId(query);
                    if (id < 0) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thi?u ID!\"}"); return; }
                    String type = query.getOrDefault("type", "bag");
                    String col = type.equals("body") ? "items_body" : (type.equals("box") ? "items_box" : "items_bag");
                    int tempId = (int) qLongOr(query, "tempid", -1);
                    int qty = (int) Math.min(9999, Math.max(1, qLongOr(query, "qty", 1)));
                    nro.template.ItemTemplate tpl = null;
                    try { tpl = models.Item.ItemService.gI().getTemplate(tempId); } catch (Exception e) {}
                    if (tpl == null) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Template kh�ng t?n t?i!\"}"); return; }
                    try {
                        jbcd.CrisResultSet rs = jbcd.ConnectDB.executeQuery(
                            "SELECT " + col + " FROM player WHERE id=?", id);
                        if (!rs.next()) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y nh�n v?t!\"}"); return; }
                        com.google.gson.JsonArray arr = jparseArr(rs.getString(col));
                        int freeSlot = -1;
                        for (int i = 0; i < arr.size(); i++) {
                            try {
                                com.google.gson.JsonArray it = new com.google.gson.JsonParser().parse(arr.get(i).getAsString()).getAsJsonArray();
                                if (Integer.parseInt(jGet(it, 0)) == -1) { freeSlot = i; break; }
                            } catch (Exception e) { freeSlot = i; break; }
                        }
                        boolean append = false;
                        if (freeSlot < 0) {
                            if (type.equals("body")) {
                                sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Trang b? ?ang ??y!\"}");
                                return;
                            }
                            if (arr.size() >= 90) {
                                sendResponse(exchange, "{\"status\": \"error\", \"msg\": \""
                                    + (type.equals("box") ? "R??ng ?� ??y!" : "H�nh trang ?� ??y!") + "\"}");
                                return;
                            }
                            append = true;
                        }
                        com.google.gson.JsonPrimitive elem = new com.google.gson.JsonPrimitive(
                            plItemElem(tempId, qty, "[]", System.currentTimeMillis()));
                        if (append) arr.add(elem); else arr.set(freeSlot, elem);
                        int targetSlot = append ? arr.size() - 1 : freeSlot;
                        jbcd.ConnectDB.executeUpdate("UPDATE player SET " + col + "=? WHERE id=?", arr.toString(), id);

                        String note = "v�o game s? �p d?ng";
                        Player p = null;
                        try { p = Client.gI().getPlayerByID(id); } catch (Exception e) {}
                        if (p != null && p.inventory != null) {
                            java.util.List<models.Item.Item> list = type.equals("body") ? p.inventory.itemsBody
                                : (type.equals("box") ? p.inventory.itemsBox : p.inventory.itemsBag);
                            models.Item.Item it = models.Item.ItemService.gI().createNewItem((short) tempId, qty);
                            if (it != null) {
                                if (targetSlot < list.size()) list.set(targetSlot, it); else list.add(it);
                                if (type.equals("body")) InventoryService.gI().sendItemBody(p);
                                else if (type.equals("box")) InventoryService.gI().sendItemBox(p);
                                else InventoryService.gI().sendItemBag(p);
                            }
                            note = "?� �p d?ng ngay v� ?ang online";
                        }
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"?� th�m [" + jsonEscape(tpl.name)
                            + "] x" + qty + "! (" + note + ")\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"L?i: "
                            + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // Sua so luong vat pham
            server.createContext("/api/pl_item_qty", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = plPlayerId(query);
                    if (id < 0) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thi?u ID!\"}"); return; }
                    String type = query.getOrDefault("type", "bag");
                    String col = type.equals("body") ? "items_body" : (type.equals("box") ? "items_box" : "items_bag");
                    int slot = (int) qLongOr(query, "slot", -1);
                    int qty = (int) Math.min(9999, Math.max(1, qLongOr(query, "qty", 1)));
                    try {
                        jbcd.CrisResultSet rs = jbcd.ConnectDB.executeQuery(
                            "SELECT " + col + " FROM player WHERE id=?", id);
                        if (!rs.next()) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y nh�n v?t!\"}"); return; }
                        com.google.gson.JsonArray arr = jparseArr(rs.getString(col));
                        if (slot < 0 || slot >= arr.size()) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Slot kh�ng h?p l?!\"}"); return; }
                        int tempId = -1;
                        String opts = "[]";
                        long createTime = System.currentTimeMillis();
                        try {
                            com.google.gson.JsonArray it = new com.google.gson.JsonParser().parse(arr.get(slot).getAsString()).getAsJsonArray();
                            tempId = Integer.parseInt(jGet(it, 0));
                            if (it.size() > 2) opts = it.get(2).toString();
                            if (it.size() > 3) createTime = Long.parseLong(jGet(it, 3));
                        } catch (Exception e) {}
                        if (tempId == -1) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"� n�y tr?ng!\"}"); return; }
                        arr.set(slot, new com.google.gson.JsonPrimitive(plItemElem(tempId, qty, opts, createTime)));
                        jbcd.ConnectDB.executeUpdate("UPDATE player SET " + col + "=? WHERE id=?", arr.toString(), id);

                        String note = "v�o game s? �p d?ng";
                        Player p = null;
                        try { p = Client.gI().getPlayerByID(id); } catch (Exception e) {}
                        if (p != null && p.inventory != null) {
                            java.util.List<models.Item.Item> list = type.equals("body") ? p.inventory.itemsBody
                                : (type.equals("box") ? p.inventory.itemsBox : p.inventory.itemsBag);
                            if (slot < list.size() && list.get(slot) != null && list.get(slot).isNotNullItem()) {
                                list.get(slot).quantity = qty;
                                if (type.equals("body")) InventoryService.gI().sendItemBody(p);
                                else if (type.equals("box")) InventoryService.gI().sendItemBox(p);
                                else InventoryService.gI().sendItemBag(p);
                            }
                            note = "?� �p d?ng ngay v� ?ang online";
                        }
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"?� s?a s? l??ng! (" + note + ")\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"L?i: "
                            + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // Luu de tu
            server.createContext("/api/pl_pet_save", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = plPlayerId(query);
                    if (id < 0) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thi?u ID!\"}"); return; }
                    try {
                        jbcd.CrisResultSet rs = jbcd.ConnectDB.executeQuery("SELECT pet FROM player WHERE id=?", id);
                        if (!rs.next()) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y nh�n v?t!\"}"); return; }
                        String petCol = rs.getString("pet");
                        com.google.gson.JsonArray petArr = jparseArr(petCol);
                        if (petCol == null || petCol.isEmpty() || petCol.equals("[]") || petArr.size() < 2) {
                            sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Nh�n v?t ch?a c� ?? t?!\"}");
                            return;
                        }
                        com.google.gson.JsonArray info = jparseArr(petArr.get(0).getAsString());
                        com.google.gson.JsonArray point = jparseArr(petArr.get(1).getAsString());
                        while (info.size() < 6) info.add(new com.google.gson.JsonPrimitive(0));
                        while (point.size() < 13) point.add(new com.google.gson.JsonPrimitive(0));

                        String petName = sanitizeName(query.getOrDefault("pet_name", jGet(info, 2)));
                        info.set(0, new com.google.gson.JsonPrimitive(qLongOr(query, "pet_type", Long.parseLong(jGet(info, 0)))));
                        info.set(1, new com.google.gson.JsonPrimitive(qLongOr(query, "pet_gender", Long.parseLong(jGet(info, 1)))));
                        info.set(2, new com.google.gson.JsonPrimitive(petName));
                        info.set(5, new com.google.gson.JsonPrimitive(qLongOr(query, "pet_status", Long.parseLong(jGet(info, 5)))));
                        jSetNum(point, 1, qLongOr(query, "pet_power", jLongVal(jGet(point, 1))));
                        jSetNum(point, 2, qLongOr(query, "pet_tiemnang", jLongVal(jGet(point, 2))));
                        jSetNum(point, 5, qLongOr(query, "pet_hpg", jLongVal(jGet(point, 5))));
                        jSetNum(point, 6, qLongOr(query, "pet_mpg", jLongVal(jGet(point, 6))));
                        jSetNum(point, 7, qLongOr(query, "pet_dameg", jLongVal(jGet(point, 7))));
                        jSetNum(point, 8, qLongOr(query, "pet_defg", jLongVal(jGet(point, 8))));
                        jSetNum(point, 9, qLongOr(query, "pet_critg", jLongVal(jGet(point, 9))));
                        petArr.set(0, new com.google.gson.JsonPrimitive(info.toString()));
                        petArr.set(1, new com.google.gson.JsonPrimitive(point.toString()));
                        jbcd.ConnectDB.executeUpdate("UPDATE player SET pet=? WHERE id=?", petArr.toString(), id);
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"?� l?u ?? t?! (nh�n v?t c?n v�o l?i game ?? th?y)\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"L?i: "
                            + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // Luu nhiem vu (chinh/side/clan/kol)
            server.createContext("/api/pl_task_save", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = plPlayerId(query);
                    if (id < 0) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thi?u ID!\"}"); return; }
                    try {
                        StringBuilder done = new StringBuilder();
                        Player online = null;
                        try { online = Client.gI().getPlayerByID(id); } catch (Exception e) {}

                        if (query.containsKey("main_id")) {
                            long taskId = qLongOr(query, "main_id", -1);
                            int idx = (int) qLongOr(query, "main_index", 0);
                            int count = (int) qLongOr(query, "main_count", 0);
                            jbcd.CrisResultSet rs = jbcd.ConnectDB.executeQuery("SELECT data_task FROM player WHERE id=?", id);
                            if (rs.next()) {
                                com.google.gson.JsonArray t = jparseArr(rs.getString("data_task"));
                                while (t.size() < 4) t.add(new com.google.gson.JsonPrimitive(0));
                                t.set(0, new com.google.gson.JsonPrimitive(taskId));
                                t.set(1, new com.google.gson.JsonPrimitive(idx));
                                t.set(2, new com.google.gson.JsonPrimitive(count));
                                jbcd.ConnectDB.executeUpdate("UPDATE player SET data_task=? WHERE id=?", t.toString(), id);
                                done.append(" ch�nh tuy?n");
                                if (online != null && taskId >= 0) {
                                    try {
                                        TaskMain newTask = TaskService.gI().getTaskMainById(online, (byte) taskId);
                                        if (newTask != null) {
                                            newTask.index = (byte) idx;
                                            if (newTask.subTasks != null && idx < newTask.subTasks.size()) {
                                                newTask.subTasks.get(idx).count = (short) count;
                                            }
                                            online.playerTask.taskMain = newTask;
                                            TaskService.gI().sendTaskMain(online);
                                            Service.gI().sendThongBao(online, "Admin ?� c?p nh?t nhi?m v? c?a b?n!");
                                        }
                                    } catch (Exception e) {}
                                }
                            }
                        }
                        if (query.containsKey("side_id")) {
                            com.google.gson.JsonArray t = new com.google.gson.JsonArray();
                            t.add(new com.google.gson.JsonPrimitive(qLongOr(query, "side_id", -1)));
                            t.add(new com.google.gson.JsonPrimitive(System.currentTimeMillis()));
                            t.add(new com.google.gson.JsonPrimitive(qLongOr(query, "side_count", 0)));
                            t.add(new com.google.gson.JsonPrimitive(qLongOr(query, "side_max", 0)));
                            t.add(new com.google.gson.JsonPrimitive(qLongOr(query, "side_left", 20)));
                            t.add(new com.google.gson.JsonPrimitive(qLongOr(query, "side_level", 0)));
                            jbcd.ConnectDB.executeUpdate("UPDATE player SET data_side_task=? WHERE id=?", t.toString(), id);
                            done.append(" h?ng ng�y");
                        }
                        if (query.containsKey("clan_id")) {
                            com.google.gson.JsonArray t = new com.google.gson.JsonArray();
                            t.add(new com.google.gson.JsonPrimitive(qLongOr(query, "clan_id", -1)));
                            t.add(new com.google.gson.JsonPrimitive(System.currentTimeMillis()));
                            t.add(new com.google.gson.JsonPrimitive(qLongOr(query, "clan_count", 0)));
                            t.add(new com.google.gson.JsonPrimitive(qLongOr(query, "clan_max", 0)));
                            t.add(new com.google.gson.JsonPrimitive(qLongOr(query, "clan_left", 20)));
                            t.add(new com.google.gson.JsonPrimitive(qLongOr(query, "clan_level", 0)));
                            jbcd.ConnectDB.executeUpdate("UPDATE player SET data_clan_task=? WHERE id=?", t.toString(), id);
                            done.append(" bang");
                        }
                        if (query.containsKey("kol_id")) {
                            com.google.gson.JsonArray t = new com.google.gson.JsonArray();
                            t.add(new com.google.gson.JsonPrimitive(qLongOr(query, "kol_id", -1)));
                            t.add(new com.google.gson.JsonPrimitive(qLongOr(query, "kol_count", 0)));
                            jbcd.ConnectDB.executeUpdate("UPDATE player SET data_kol_task=? WHERE id=?", t.toString(), id);
                            done.append(" KOL");
                        }

                        String msg = done.length() > 0 ? ("?� l?u nhi?m v?:" + done + "!")
                            : "Kh�ng c� g� thay ??i!";
                        if (done.indexOf("ch�nh tuy?n") >= 0 && online == null) {
                            msg += " (nhi?m v? ch�nh tuy?n �p d?ng khi v�o game)";
                        }
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"" + msg + "\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"L?i: "
                            + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // Them danh hieu
            server.createContext("/api/pl_badge_add", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = plPlayerId(query);
                    int badgeId = (int) qLongOr(query, "badge_id", -1);
                    long days = Math.max(1, qLongOr(query, "days", 30));
                    if (id < 0 || badgeId < 0) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thi?u tham s?!\"}"); return; }
                    try {
                        jbcd.CrisResultSet rs = jbcd.ConnectDB.executeQuery("SELECT dataBadges FROM player WHERE id=?", id);
                        if (!rs.next()) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y nh�n v?t!\"}"); return; }
                        com.google.gson.JsonArray arr = jparseArr(rs.getString("dataBadges"));
                        if (arr.size() >= 50) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"T?i ?a 50 danh hi?u!\"}"); return; }
                        com.google.gson.JsonObject obj = new com.google.gson.JsonObject();
                        obj.addProperty("idBadGes", badgeId);
                        obj.addProperty("timeofUseBadges", System.currentTimeMillis() + days * 86400000L);
                        obj.addProperty("isUse", false);
                        arr.add(obj);
                        jbcd.ConnectDB.executeUpdate("UPDATE player SET dataBadges=? WHERE id=?", arr.toString(), id);
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"?� th�m danh hi?u #" + badgeId
                            + " (" + days + " ng�y)! Nh�n v?t v�o l?i game s? th?y.\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"L?i: "
                            + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // Xoa danh hieu theo index
            server.createContext("/api/pl_badge_del", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = plPlayerId(query);
                    int idx = (int) qLongOr(query, "idx", -1);
                    if (id < 0) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thi?u ID!\"}"); return; }
                    try {
                        jbcd.CrisResultSet rs = jbcd.ConnectDB.executeQuery("SELECT dataBadges FROM player WHERE id=?", id);
                        if (!rs.next()) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y nh�n v?t!\"}"); return; }
                        com.google.gson.JsonArray arr = jparseArr(rs.getString("dataBadges"));
                        if (idx < 0 || idx >= arr.size()) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Index kh�ng h?p l?!\"}"); return; }
                        arr.remove(idx);
                        jbcd.ConnectDB.executeUpdate("UPDATE player SET dataBadges=? WHERE id=?", arr.toString(), id);
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"?� x�a danh hi?u!\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"L?i: "
                            + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // Bat/tat danh hieu + gia han
            server.createContext("/api/pl_badge_toggle", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    long id = plPlayerId(query);
                    int idx = (int) qLongOr(query, "idx", -1);
                    int use = (int) qLongOr(query, "use", -1);
                    long days = qLongOr(query, "days", 0);
                    if (id < 0) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thi?u ID!\"}"); return; }
                    try {
                        jbcd.CrisResultSet rs = jbcd.ConnectDB.executeQuery("SELECT dataBadges FROM player WHERE id=?", id);
                        if (!rs.next()) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Kh�ng t�m th?y nh�n v?t!\"}"); return; }
                        com.google.gson.JsonArray arr = jparseArr(rs.getString("dataBadges"));
                        if (idx < 0 || idx >= arr.size()) { sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Index kh�ng h?p l?!\"}"); return; }
                        com.google.gson.JsonObject obj = arr.get(idx).getAsJsonObject();
                        if (use == 0 || use == 1) obj.addProperty("isUse", use == 1);
                        if (days > 0) obj.addProperty("timeofUseBadges",
                            Math.max(obj.has("timeofUseBadges") ? obj.get("timeofUseBadges").getAsLong() : 0,
                                System.currentTimeMillis()) + days * 86400000L);
                        jbcd.ConnectDB.executeUpdate("UPDATE player SET dataBadges=? WHERE id=?", arr.toString(), id);
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"?� c?p nh?t danh hi?u!\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"L?i: "
                            + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });
            // ================= END QUAN LY NHAN VAT =================

            server.createContext("/api/send_notice", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    String msg = query.get("msg");
                    if (msg == null || msg.isEmpty()) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thi?u n?i dung th�ng b�o!\"}");
                        return;
                    }
                    Service.gI().sendThongBaoAllPlayer(msg);
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� g?i th�ng b�o t?i to�n server!\"}");
                }
            });

            server.createContext("/api/reload_drop", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    DropManager.gI().reload();
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� n?p l?i d? li?u Drop Item!\"}");
                }
            });

            server.createContext("/api/reload_npc", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    try {
                        Manager.NPC_TEMPLATES.clear();
                        String sql = "SELECT * FROM npc_template";
                        try (java.sql.Connection con = jbcd.ConnectDB.getConnection();
                             java.sql.PreparedStatement ps = con.prepareStatement(sql);
                             java.sql.ResultSet rs = ps.executeQuery()) {
                            while (rs.next()) {
                                nro.template.NpcTemplate npcTemp = new nro.template.NpcTemplate();
                                npcTemp.id = rs.getByte("id");
                                npcTemp.name = rs.getString("name");
                                npcTemp.head = rs.getShort("head");
                                npcTemp.body = rs.getShort("body");
                                npcTemp.leg = rs.getShort("leg");
                                npcTemp.avatar = rs.getInt("avatar");
                                Manager.NPC_TEMPLATES.add(npcTemp);
                            }
                        }
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� n?p l?i " + Manager.NPC_TEMPLATES.size() + " NPC!\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"L?i n?p l?i NPC: " + e.getMessage() + "\"}");
                    }
                }
            });

            server.createContext("/api/data_switch", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    String v = query.get("val");
                    if ("0".equals(v)) {
                        Manager.readInt = true;
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� chuy?n d? li?u sang INT!\"}");
                    } else if ("1".equals(v)) {
                        Manager.readInt = false;
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Đ� chuy?n d? li?u sang LONG!\"}");
                    } else {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Gi� tr? kh�ng h?p l?!\"}");
                    }
                }
            });

            // === Maintenance Scheduler (daily at hour:minute) ===
            server.createContext("/api/maintenance_set_time", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    String hStr = query.get("hour");
                    String mStr = query.get("minute");
                    String ar = query.get("auto_restart");
                    if (hStr == null || mStr == null) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thieu tham so hour, minute!\"}");
                        return;
                    }
                    try {
                        int h = Integer.parseInt(hStr);
                        int m = Integer.parseInt(mStr);
                        if (h < 0 || h > 23 || m < 0 || m > 59) {
                            sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Gia tri hour (0-23), minute (0-59)!\"}");
                            return;
                        }
                        maintHour = h;
                        maintMinute = m;
                        maintAutoRestart = "1".equals(ar) || "true".equals(ar);
                        saveMaintenanceConfig();
                        scheduleDailyMaintenance();
                        String msg = "Da dat lich bao tri luc " + String.format("%02d:%02d", h, m)
                                + (maintAutoRestart ? " (AutoRestart ON)" : "");
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"" + jsonEscape(msg) + "\"}");
                    } catch (NumberFormatException e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Hour/Minute phai la so!\"}");
                    }
                }
            });

            server.createContext("/api/maintenance_cancel", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    if (maintenanceTask != null) {
                        maintenanceTask.cancel(false);
                        maintenanceTask = null;
                    }
                    if (maintenanceScheduler != null) {
                        maintenanceScheduler.shutdownNow();
                        maintenanceScheduler = null;
                    }
                    maintHour = -1;
                    maintMinute = -1;
                    sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Da huy lich bao tri!\"}");
                }
            });

            // === Session Clean ===
            server.createContext("/api/session_clean", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    try {
                        java.util.List<network.interfaces.ISession> sessions = network.session.SessionManager.gI().getSessions();
                        int before = sessions.size();
                        java.util.Iterator<network.interfaces.ISession> it = sessions.iterator();
                        int removed = 0;
                        while (it.hasNext()) {
                            network.interfaces.ISession s = it.next();
                            try {
                                if (s == null || !s.isConnected()) { it.remove(); removed++; }
                            } catch (Exception e) {
                                it.remove();
                                removed++;
                            }
                        }
                        String msg = "Da don " + removed + "/" + before + " session!";
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"" + jsonEscape(msg) + "\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Loi don session: " + e.getMessage() + "\"}");
                    }
                }
            });

            // === Event Config Save ===
            server.createContext("/api/event_save", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    try {
                        boolean[] states = eventStates();
                        StringBuilder ids = new StringBuilder();
                        for (int i = 0; i < states.length; i++) {
                            if (states[i]) {
                                if (ids.length() > 0) ids.append("-");
                                ids.append(i);
                            }
                        }
                        String activeIds = ids.length() > 0 ? ids.toString() : "0";
                        try (PrintWriter pw = new PrintWriter(new FileWriter("active_event.txt"))) {
                            pw.println(activeIds);
                            pw.flush();
                        }
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Da luu cau hinh su kien: " + jsonEscape(activeIds) + "\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Loi luu su kien: " + e.getMessage() + "\"}");
                    }
                }
            });

            // === Optimize (session clean + GC) ===
            server.createContext("/api/optimize", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    try {
                        long before = Runtime.getRuntime().totalMemory() - Runtime.getRuntime().freeMemory();
                        System.gc();
                        long after = Runtime.getRuntime().totalMemory() - Runtime.getRuntime().freeMemory();
                        long freed = Math.max(0, before - after);
                        String msg = "Da toi uu! RAM giai phong: " + (freed / 1024 / 1024) + "MB";
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"" + jsonEscape(msg) + "\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Loi optimize: " + e.getMessage() + "\"}");
                    }
                }
            });

            // === Port tu Hashirama: tat boss theo id ===
            server.createContext("/api/boss_kill", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    int id = -1;
                    try { id = Integer.parseInt(query.getOrDefault("id", "-1")); } catch (Exception e) {}
                    if (id < 0) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thieu boss id!\"}");
                        return;
                    }
                    try {
                        QuanLiBoss.Boss target = null;
                        for (QuanLiBoss.Boss b : QuanLiBoss.Manager.BossManager.gI().getBosses()) {
                            if (b != null && b.id == id) { target = b; break; }
                        }
                        if (target == null) {
                            sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Khong tim thay boss id=" + id + "\"}");
                            return;
                        }
                        if (target.isDie()) {
                            sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Boss " + id + " da chet roi\"}");
                            return;
                        }
                        target.die();
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Da tat boss " + id + " thanh cong\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Loi: " + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // === Port tu Hashirama: tim kiem tai khoan ===
            server.createContext("/api/account_search", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    String q = query.getOrDefault("q", "").replaceAll("[^a-zA-Z0-9_.]", "");
                    try {
                        String sql = "SELECT id, username, ban, is_admin, vnd, tongnap FROM account"
                                + (q.isEmpty() ? "" : " WHERE username LIKE '%" + q.replace("'", "") + "%'")
                                + " ORDER BY id DESC LIMIT 50";
                        jbcd.CrisResultSet rs = jbcd.ConnectDB.executeQuery(sql);
                        StringBuilder arr = new StringBuilder("[");
                        boolean first = true;
                        while (rs.next()) {
                            if (!first) arr.append(",");
                            arr.append("{\"id\":").append(rs.getInt("id"))
                               .append(",\"username\":\"").append(jsonEscape(rs.getString("username"))).append("\"")
                               .append(",\"ban\":").append(rs.getInt("ban"))
                               .append(",\"is_admin\":").append(rs.getInt("is_admin"))
                               .append(",\"vnd\":").append(rs.getInt("vnd"))
                               .append(",\"tongnap\":").append(rs.getInt("tongnap")).append("}");
                            first = false;
                        }
                        arr.append("]");
                        sendResponse(exchange, "{\"success\": true, \"accounts\": " + arr + "}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"success\": false, \"message\": \"" + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // === Port tu Hashirama: doi mat khau ===
            server.createContext("/api/acc_setpass", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    String u = query.getOrDefault("username", "").trim();
                    String p = query.getOrDefault("password", "");
                    if (u.isEmpty() || p.length() < 4) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Sai tham so / MK >= 4 ky tu!\"}");
                        return;
                    }
                    try {
                        int n = jbcd.ConnectDB.executeUpdate("UPDATE account SET password=? WHERE username=?", p, u);
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"" + (n > 0 ? "Da doi mat khau cho " + jsonEscape(u) : "Khong tim thay TK") + "\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Loi: " + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // === Port tu Hashirama: khoa / mo khoa ===
            server.createContext("/api/acc_setban", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    String u = query.getOrDefault("username", "").trim();
                    int val = 0;
                    try { val = Integer.parseInt(query.getOrDefault("val", "0")); } catch (Exception e) {}
                    if (u.isEmpty()) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thieu username!\"}");
                        return;
                    }
                    try {
                        int n = jbcd.ConnectDB.executeUpdate("UPDATE account SET ban=? WHERE username=?", val, u);
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"" + (n > 0 ? (val != 0 ? "Da khoa " : "Da mo khoa ") + jsonEscape(u) : "Khong tim thay TK") + "\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Loi: " + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // === Port tu Hashirama: cap / go quyen admin (giu lai admin cuoi) ===
            server.createContext("/api/acc_setadmin", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    String u = query.getOrDefault("username", "").trim();
                    int val = 0;
                    try { val = Integer.parseInt(query.getOrDefault("val", "0")); } catch (Exception e) {}
                    if (u.isEmpty()) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thieu username!\"}");
                        return;
                    }
                    try {
                        if (val == 0) {
                            int admins = 0;
                            boolean isAdminNow = false;
                            jbcd.CrisResultSet rs = jbcd.ConnectDB.executeQuery("SELECT COUNT(*) AS c FROM account WHERE is_admin=1");
                            if (rs.next()) admins = rs.getInt("c");
                            jbcd.CrisResultSet rs2 = jbcd.ConnectDB.executeQuery("SELECT is_admin FROM account WHERE username=? LIMIT 1", u);
                            if (rs2.next()) isAdminNow = rs2.getInt("is_admin") == 1;
                            if (isAdminNow && admins <= 1) {
                                sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Khong the go quyen admin cuoi cung!\"}");
                                return;
                            }
                        }
                        int n = jbcd.ConnectDB.executeUpdate("UPDATE account SET is_admin=? WHERE username=?", val, u);
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"" + (n > 0 ? (val != 0 ? "Da cap quyen admin cho " : "Da go quyen admin cua ") + jsonEscape(u) : "Khong tim thay TK") + "\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Loi: " + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // === Port tu Hashirama (adapt napthe): danh sach the nap cho duyet ===
            server.createContext("/api/nap_card_list", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    try {
                        jbcd.CrisResultSet rs = jbcd.ConnectDB.executeQuery(
                            "SELECT id, user_nap, telco, serial, code, amount, status, created_at FROM napthe ORDER BY id DESC LIMIT 50");
                        StringBuilder arr = new StringBuilder("[");
                        boolean first = true;
                        while (rs.next()) {
                            if (!first) arr.append(",");
                            arr.append("{\"id\":").append(rs.getInt("id"))
                               .append(",\"username\":\"").append(jsonEscape(rs.getString("user_nap"))).append("\"")
                               .append(",\"card_type\":\"").append(jsonEscape(rs.getString("telco"))).append("\"")
                               .append(",\"card_seri\":\"").append(jsonEscape(rs.getString("serial"))).append("\"")
                               .append(",\"card_code\":\"").append(jsonEscape(rs.getString("code"))).append("\"")
                               .append(",\"amount\":").append(rs.getInt("amount"))
                               .append(",\"status\":").append(rs.getInt("status"))
                               .append(",\"created_at\":\"").append(jsonEscape(String.valueOf(rs.getTimestamp("created_at")))).append("\"}")
                               ;
                            first = false;
                        }
                        arr.append("]");
                        sendResponse(exchange, "{\"success\": true, \"list\": " + arr + "}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"success\": false, \"message\": \"" + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // === Port tu Hashirama (adapt napthe): duyet the -> cong vnd/tongnap ===
            server.createContext("/api/nap_card_approve", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    int id = 0;
                    try { id = Integer.parseInt(query.getOrDefault("val", query.getOrDefault("id", "0"))); } catch (Exception e) {}
                    if (id <= 0) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thieu id!\"}");
                        return;
                    }
                    try {
                        jbcd.CrisResultSet rs = jbcd.ConnectDB.executeQuery("SELECT * FROM napthe WHERE id=? AND status IN (0,99)", id);
                        if (!rs.next()) {
                            sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Yeu cau khong ton tai hoac da duyet!\"}");
                            return;
                        }
                        String user = rs.getString("user_nap");
                        int amt = rs.getInt("amount");
                        int n = jbcd.ConnectDB.executeUpdate("UPDATE account SET vnd = vnd + ?, tongnap = tongnap + ? WHERE username=?", amt, amt, user);
                        if (n <= 0) {
                            sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Khong tim thay TK " + jsonEscape(user) + "\"}");
                            return;
                        }
                        jbcd.ConnectDB.executeUpdate("UPDATE napthe SET status=1 WHERE id=?", id);
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Da duyet nap " + amt + " VND cho " + jsonEscape(user) + "\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Loi: " + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // === Port tu Hashirama (adapt napthe): tu choi the ===
            server.createContext("/api/nap_card_reject", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    int id = 0;
                    try { id = Integer.parseInt(query.getOrDefault("val", query.getOrDefault("id", "0"))); } catch (Exception e) {}
                    if (id <= 0) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thieu id!\"}");
                        return;
                    }
                    try {
                        jbcd.ConnectDB.executeUpdate("UPDATE napthe SET status=2 WHERE id=?", id);
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Da tu choi yeu cau nap #" + id + "\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Loi: " + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // === Port tu Hashirama: top nap tien ===
            server.createContext("/api/top_nap", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    try {
                        jbcd.CrisResultSet rs = jbcd.ConnectDB.executeQuery(
                            "SELECT a.id, a.username, a.tongnap, (SELECT p.name FROM player p WHERE p.account_id=a.id LIMIT 1) AS char_name FROM account a ORDER BY a.tongnap DESC LIMIT 20");
                        StringBuilder arr = new StringBuilder("[");
                        boolean first = true;
                        int rank = 1;
                        while (rs.next()) {
                            if (!first) arr.append(",");
                            arr.append("{\"rank\":").append(rank++)
                               .append(",\"username\":\"").append(jsonEscape(rs.getString("username"))).append("\"")
                               .append(",\"char_name\":\"").append(jsonEscape(rs.getString("char_name") == null ? "" : rs.getString("char_name"))).append("\"")
                               .append(",\"tongnap\":").append(rs.getInt("tongnap")).append("}");
                            first = false;
                        }
                        arr.append("]");
                        sendResponse(exchange, "{\"success\": true, \"list\": " + arr + "}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"success\": false, \"message\": \"" + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // === Port tu Hashirama: top suc manh (power nam trong data_point) ===
            server.createContext("/api/top_power", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    try {
                        jbcd.CrisResultSet rs = jbcd.ConnectDB.executeQuery("SELECT id, name, data_point FROM player");
                        java.util.List<long[]> rows = new java.util.ArrayList<>();
                        java.util.Map<Integer, String> names = new java.util.HashMap<>();
                        while (rs.next()) {
                            long power = 0;
                            int pid = 0;
                            try {
                                com.google.gson.JsonArray dp = jparseArr(rs.getString("data_point"));
                                power = jLongVal(jGet(dp, 1));
                            } catch (Exception ignored) {}
                            try { pid = rs.getInt("id"); } catch (Exception ignored) {}
                            rows.add(new long[]{pid, power});
                            try { names.put(pid, rs.getString("name")); } catch (Exception ignored) {}
                        }
                        rows.sort((a, b) -> Long.compare(b[1], a[1]));
                        StringBuilder arr = new StringBuilder("[");
                        for (int i = 0; i < rows.size() && i < 20; i++) {
                            if (i > 0) arr.append(",");
                            String nm = names.get((int) rows.get(i)[0]);
                            arr.append("{\"rank\":").append(i + 1)
                               .append(",\"name\":\"").append(jsonEscape(nm == null ? "" : nm)).append("\"")
                               .append(",\"power\":").append(rows.get(i)[1])
                               .append(",\"pid\":").append(rows.get(i)[0]).append("}");
                        }
                        arr.append("]");
                        sendResponse(exchange, "{\"success\": true, \"list\": " + arr + "}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"success\": false, \"message\": \"" + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // === Port tu Hashirama: mo khoa tai khoan ===
            server.createContext("/api/player_unban", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    String name = query.getOrDefault("name", "").trim();
                    if (name.isEmpty()) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Thieu name!\"}");
                        return;
                    }
                    try {
                        int n = jbcd.ConnectDB.executeUpdate("UPDATE account SET ban=0 WHERE username=?", name);
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"" + (n > 0 ? "Da mo khoa " + jsonEscape(name) : "Khong tim thay TK") + "\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Loi: " + jsonEscape(String.valueOf(e.getMessage())) + "\"}");
                    }
                }
            });

            // === Port tu Hashirama: gioi han IP / nguoi choi / khuyen mai ===
            server.createContext("/api/max_per_ip", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    try {
                        int v = Integer.parseInt(query.getOrDefault("val", "10"));
                        if (v < 1) v = 1;
                        Manager.MAX_PER_IP = v;
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Da dat max/IP = " + v + "\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Gia tri khong hop le!\"}");
                    }
                }
            });
            server.createContext("/api/max_player", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    try {
                        int v = Integer.parseInt(query.getOrDefault("val", "1000"));
                        if (v < 1) v = 1;
                        Manager.MAX_PLAYER = v;
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Da dat max player = " + v + "\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Gia tri khong hop le!\"}");
                    }
                }
            });
            server.createContext("/api/khuyen_mai", new HttpHandler() {
                @Override
                public void handle(HttpExchange exchange) throws IOException {
                    Map<String, String> query = queryToMap(exchange.getRequestURI().getQuery());
                    try {
                        int v = Integer.parseInt(query.getOrDefault("val", "1"));
                        if (v < 1) v = 1;
                        if (v > 100) v = 100;
                        Manager.KHUYEN_MAI_NAP = (byte) v;
                        sendResponse(exchange, "{\"status\": \"success\", \"msg\": \"Khuyen mai nap = x" + v + "\"}");
                    } catch (Exception e) {
                        sendResponse(exchange, "{\"status\": \"error\", \"msg\": \"Gia tri khong hop le!\"}");
                    }
                }
            });

            // Dung thread pool (khong dung executor mac dinh single-thread) de cac request
            // admin (dashboard poll /api/info, bot status, presence, chats...) chay song song.
            // Neu chay 1 thread: 1 handler cham lam ket dong /api/info -> curl PHP timeout 4s
            // -> dashboard hien so 0 + spam IOException khi ghi vao socket da dong.
            // Gioi han 8 thread de an toan voi DB pool (max=20) va tai game.
            server.setExecutor(Executors.newFixedThreadPool(8, r -> {
                Thread t = new Thread(r, "WebAdminAPI-Worker");
                t.setDaemon(true);
                return t;
            }));
            server.start();
            System.out.println("WebAdminAPI started on port " + PORT);
        } catch (Exception e) {
            System.out.println("Error starting WebAdminAPI: " + e.getMessage());
        }
    }

    private static boolean[] eventStates() {
        boolean[] states = new boolean[12];
        states[0] = false;
        states[1] = EventManager.LUNNAR_NEW_YEAR;
        states[2] = EventManager.TRUNG_THU;
        states[3] = EventManager.HALLOWEEN;
        states[4] = EventManager.CHRISTMAS;
        states[5] = EventManager.VU_LAN_FESTIVAL;
        states[6] = EventManager.INTERNATIONAL_WOMANS_DAY;
        states[7] = EventManager.HUNG_VUONG;
        states[8] = EventManager.BLACK_FRIDAY;
        states[9] = EventManager.VALENTINE_DAY;
        states[10] = EventManager.DAY_20_10;
        states[11] = EventManager.TOP_UP;
        return states;
    }

    private static String jsonEscape(String s) {
        if (s == null) return "";
        return s.replace("\\", "\\\\").replace("\"", "\\\"").replace("\n", "\\n").replace("\r", "\\r");
    }

    // ================== MAINTENANCE SCHEDULER ==================

    private static final String MAINT_CONFIG_FILE = "maintenanceConfig.txt";

    private static void saveMaintenanceConfig() {
        try (PrintWriter pw = new PrintWriter(new FileWriter(MAINT_CONFIG_FILE))) {
            pw.println(maintHour);
            pw.println(maintMinute);
            pw.println(0);
            pw.println(maintAutoRestart);
            pw.println(true);
            pw.flush();
        } catch (Exception e) {
            System.out.println("Loi luu maintenance config: " + e.getMessage());
        }
    }

    private static void loadMaintenanceConfig() {
        try {
            java.io.BufferedReader br = new java.io.BufferedReader(new java.io.FileReader(MAINT_CONFIG_FILE));
            String line;
            int lineNum = 0;
            while ((line = br.readLine()) != null) {
                line = line.trim();
                lineNum++;
                switch (lineNum) {
                    case 1: maintHour = Integer.parseInt(line); break;
                    case 2: maintMinute = Integer.parseInt(line); break;
                    case 4: maintAutoRestart = Boolean.parseBoolean(line); break;
                }
            }
            br.close();
            if (maintHour >= 0 && maintHour <= 23 && maintMinute >= 0 && maintMinute <= 59) {
                scheduleDailyMaintenance();
                System.out.println("Maintenance scheduler loaded: " + String.format("%02d:%02d", maintHour, maintMinute));
            }
        } catch (Exception e) {
            // Config file not found or invalid, skip
        }
    }

    private static void scheduleDailyMaintenance() {
        if (maintenanceTask != null) {
            maintenanceTask.cancel(false);
        }
        if (maintenanceScheduler == null || maintenanceScheduler.isShutdown()) {
            maintenanceScheduler = Executors.newSingleThreadScheduledExecutor();
        }
        maintenanceTask = maintenanceScheduler.scheduleAtFixedRate(() -> {
            try {
                Calendar now = Calendar.getInstance();
                int curH = now.get(Calendar.HOUR_OF_DAY);
                int curM = now.get(Calendar.MINUTE);
                if (curH == maintHour && curM == maintMinute) {
                    System.out.println("Maintenance scheduler triggered at " + String.format("%02d:%02d", curH, curM));
                    nro.server.Maintenance.gI().start(120);
                }
            } catch (Exception e) {
                System.out.println("Maintenance scheduler error: " + e.getMessage());
            }
        }, 0, 30, TimeUnit.SECONDS);
    }

    // ================== VP CONFIG SAVE ==================

    private static void saveVpConfig(nro.virtualplayer.VirtualConfig cfg) {
        try (PrintWriter pw = new PrintWriter(new FileWriter("virtualplayer_config.txt"))) {
            pw.println("# Virtual Player Configuration");
            pw.println("enabled=" + cfg.enabled);
            pw.println("population=" + cfg.population);
            pw.println("exp_rate=" + cfg.expRate);
            pw.println("gold_rate=" + cfg.goldRate);
            pw.println("chat_rate=" + cfg.chatRate);
            pw.println("map_change_rate=" + cfg.mapChangeRate);
            pw.println("player_protection=" + cfg.playerProtection);
            pw.println("gift_rate=" + cfg.giftRate);
            pw.println("afk_rate=" + cfg.afkRate);
            pw.println();
            pw.println("# So bot AI luon giu hoat dong gan MOI nguoi choi that (0 = tat, bot song hoan toan doc lap)");
            pw.println("presence_per_player=" + cfg.presencePerPlayer);
            pw.println("# Thoi gian (giay) moi bot ghe tham khu nguoi choi truoc khi nhuong luot cho bot khac");
            pw.println("presence_visit_seconds=" + cfg.presenceVisitSeconds);
            pw.flush();
        } catch (Exception e) {
            System.out.println("Loi luu VP config: " + e.getMessage());
        }
    }

    /**
     * C?p nh?t l?i h�nh hi?n th? c?a bot cho nh?ng ng?ời ch?i xung quanh
     * (d�ng sau khi ??i trang b?).
     */
    private static void refreshOutfit(nro.virtualplayer.VirtualPlayer vp) {
        try {
            if (vp.zone != null) {
                vp.zone.load_Me_To_Another(vp);
            }
        } catch (Exception e) {
        }
    }

    // ================== HELPERS QUAN LY NHAN VAT ==================

    private static com.google.gson.JsonArray jparseArr(String s) {
        try {
            return new com.google.gson.JsonParser().parse(s == null || s.trim().isEmpty() ? "[]" : s).getAsJsonArray();
        } catch (Exception e) {
            return new com.google.gson.JsonArray();
        }
    }

    private static String jGet(com.google.gson.JsonArray a, int i) {
        try {
            if (a == null || i < 0 || i >= a.size()) return "0";
            return a.get(i).toString().replace("\"", "");
        } catch (Exception e) {
            return "0";
        }
    }

    private static long jLongVal(String s) {
        try {
            String clean = s == null ? "0" : s.replaceAll("[^0-9-]", "");
            return clean.isEmpty() ? 0 : Long.parseLong(clean);
        } catch (Exception e) {
            return 0;
        }
    }

    private static int jIntVal(String s) {
        return (int) jLongVal(s);
    }

    private static void jSetNum(com.google.gson.JsonArray arr, int idx, long val) {
        while (arr.size() <= idx) arr.add(new com.google.gson.JsonPrimitive(0));
        try {
            arr.set(idx, new com.google.gson.JsonPrimitive(val));
        } catch (Exception e) {}
    }

    private static long qLongOr(Map<String, String> query, String key, long def) {
        try {
            if (!query.containsKey(key)) return def;
            String s = query.get(key).replaceAll("[^0-9-]", "");
            return s.isEmpty() ? def : Long.parseLong(s);
        } catch (Exception e) {
            return def;
        }
    }

    private static long plPlayerId(Map<String, String> query) {
        return qLongOr(query, "id", -1);
    }

    private static String sanitizeName(String s) {
        if (s == null) return "";
        return s.replace("\\", "").replace("\"", "").replace("'", "").trim();
    }

    /**
     * Dung phan tu vat pham theo format DB: [tempId, qty, "[[optId,param],...]", createTime]
     */
    private static String plItemElem(int tempId, int qty, String opts, long createTime) {
        String o = opts == null ? "" : opts.replace("\"", "").trim();
        if (!o.startsWith("[")) o = "[]";
        return "[" + tempId + "," + qty + ",\"" + o + "\"," + createTime + "]";
    }

    private static String plOptionName(int id, long param) {
        try {
            for (nro.template.ItemOptionTemplate t : Manager.ITEM_OPTION_TEMPLATES) {
                if (t != null && t.id == id) return t.name.replace("#", String.valueOf(param));
            }
        } catch (Exception e) {}
        return "Option " + id + ": +" + param;
    }

    /**
     * Chuyen cot items_body/items_bag/items_box thanh JSON danh s�ch vat pham co ten+icon.
     */
    private static String plItemsJson(String colJson) {
        com.google.gson.JsonArray arr = jparseArr(colJson);
        StringBuilder out = new StringBuilder();
        for (int i = 0; i < arr.size(); i++) {
            try {
                com.google.gson.JsonArray it = new com.google.gson.JsonParser().parse(arr.get(i).getAsString()).getAsJsonArray();
                int tempId = Integer.parseInt(jGet(it, 0));
                if (tempId == -1) continue;
                int qty = Integer.parseInt(jGet(it, 1));
                String optsRaw = it.size() > 2 ? it.get(2).getAsString() : "[]";

                StringBuilder optPairs = new StringBuilder();
                StringBuilder optReadable = new StringBuilder();
                try {
                    com.google.gson.JsonArray opts = new com.google.gson.JsonParser().parse(optsRaw.replace("\"", "")).getAsJsonArray();
                    for (int j = 0; j < opts.size(); j++) {
                        com.google.gson.JsonArray op = opts.get(j).getAsJsonArray();
                        int oid = op.get(0).getAsInt();
                        long oval = op.get(1).getAsLong();
                        if (optPairs.length() > 0) optPairs.append(",");
                        optPairs.append("[").append(oid).append(",").append(oval).append("]");
                        if (optReadable.length() > 0) optReadable.append("; ");
                        optReadable.append(plOptionName(oid, oval));
                    }
                } catch (Exception e) {}

                String name = "?" ;
                int icon = -1;
                try {
                    nro.template.ItemTemplate tpl = models.Item.ItemService.gI().getTemplate(tempId);
                    if (tpl != null) { name = tpl.name; icon = tpl.iconID; }
                } catch (Exception e) {}

                if (out.length() > 0) out.append(",");
                out.append("{\"slot\":").append(i)
                   .append(",\"tempid\":").append(tempId)
                   .append(",\"qty\":").append(qty)
                   .append(",\"name\":\"").append(jsonEscape(name)).append("\"")
                   .append(",\"icon\":").append(icon)
                   .append(",\"opts\":\"").append(jsonEscape(optPairs.toString())).append("\"")
                   .append(",\"optstr\":\"").append(jsonEscape(optReadable.toString())).append("\"")
                   .append("}");
            } catch (Exception e) {}
        }
        return "[" + out.toString() + "]";
    }

    /**
     * Doc cot pet thanh JSON: {"exists":..,"type":..,"gender":..,"name":..,"status":..,"power":..}
     */
    private static String plPetJson(String petCol) {
        com.google.gson.JsonArray petArr = jparseArr(petCol);
        if (petCol == null || petCol.trim().isEmpty() || petCol.trim().equals("[]") || petArr.size() < 2) {
            return "{\"exists\": false}";
        }
        try {
            com.google.gson.JsonArray info = jparseArr(petArr.get(0).getAsString());
            com.google.gson.JsonArray point = jparseArr(petArr.get(1).getAsString());
            return "{\"exists\": true"
                + ",\"type\": " + jIntVal(jGet(info, 0))
                + ",\"gender\": " + jIntVal(jGet(info, 1))
                + ",\"name\": \"" + jsonEscape(jGet(info, 2)) + "\""
                + ",\"status\": " + jIntVal(jGet(info, 5))
                + ",\"power\": " + jLongVal(jGet(point, 1))
                + ",\"tiemnang\": " + jLongVal(jGet(point, 2))
                + ",\"hpg\": " + jLongVal(jGet(point, 5))
                + ",\"mpg\": " + jLongVal(jGet(point, 6))
                + ",\"dameg\": " + jLongVal(jGet(point, 7))
                + ",\"defg\": " + jLongVal(jGet(point, 8))
                + ",\"critg\": " + jLongVal(jGet(point, 9))
                + "}";
        } catch (Exception e) {
            return "{\"exists\": false}";
        }
    }

    private static String plBadgesJson(String col) {
        com.google.gson.JsonArray arr = jparseArr(col);
        StringBuilder out = new StringBuilder();
        for (int i = 0; i < arr.size(); i++) {
            try {
                com.google.gson.JsonObject o = arr.get(i).getAsJsonObject();
                if (out.length() > 0) out.append(",");
                out.append("{\"idx\":").append(i)
                   .append(",\"id\":").append(o.has("idBadGes") ? o.get("idBadGes").getAsInt() : -1)
                   .append(",\"time\":").append(o.has("timeofUseBadges") ? o.get("timeofUseBadges").getAsLong() : 0)
                   .append(",\"use\":").append(o.has("isUse") && o.get("isUse").getAsBoolean()).append("}");
            } catch (Exception e) {}
        }
        return "[" + out.toString() + "]";
    }

    private static void sendResponse(HttpExchange exchange, String response) {
        try {
            exchange.getResponseHeaders().set("Content-Type", "application/json; charset=UTF-8");
            exchange.getResponseHeaders().set("Access-Control-Allow-Origin", "*");
            byte[] bytes = response.getBytes("UTF-8");
            exchange.sendResponseHeaders(200, bytes.length);
            try (OutputStream os = exchange.getResponseBody()) {
                os.write(bytes);
            }
        } catch (IOException ioe) {
            // Client (thuong la PHP proxy voi timeout ngan) da dong ket noi truoc khi
            // response duoc ghi xong -> bo qua. Day la loi vo hai, khong log de tranh spam console.
        } finally {
            exchange.close();
        }
    }

    private static Map<String, String> queryToMap(String query) {
        Map<String, String> result = new HashMap<>();
        if (query == null) return result;
        for (String param : query.split("&")) {
            String[] entry = param.split("=");
            if (entry.length > 1) {
                result.put(entry[0], decode(entry[1]));
            } else {
                result.put(entry[0], "");
            }
        }
        return result;
    }

    private static String decode(String s) {
        try {
            return java.net.URLDecoder.decode(s, "UTF-8");
        } catch (Exception e) {
            return s;
        }
    }
}
