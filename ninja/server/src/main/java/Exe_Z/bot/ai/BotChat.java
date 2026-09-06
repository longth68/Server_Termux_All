package Exe_Z.bot.ai;

import Exe_Z.bot.AutoFarmBot;
import Exe_Z.model.Char;
import Exe_Z.util.Log;
import Exe_Z.util.NinjaUtils;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.List;

/**
 * Port từ NRO VirtualChat: chat theo personality + relation + state.
 * Kho câu lớn, chọn chống lặp (không dùng lại câu gần đây), có biến thể
 * thêm cảm thán/số liệu để cùng một mẫu cho cảm giác tự nhiên như người chơi.
 */
public class BotChat {

    private static final List<String> ROAM = Arrays.asList(
        "Ai train chung khong?", "Map nay kha on do", "Khu nay dong vui ghe",
        "Minh moi qua map nay", "Di mot minh hoi buon", "Map nay de train ky nang ne",
        "Hoi nay server trong qua", "Co ai o khu nay khong?", "Lang quet map nay xem sao",
        "Toi nay gap nhau cai bang khong?", "Sap len cap roi me", "Map sau quai nhieu hon khong ta?",
        "Nghe noi map sau roi do hiem", "Di lang than ky di do khong?", "Thien dia hoi de hay kho ta?",
        "Nguoi choi moi lam sao nang cap nhanh nhi?", "Ai biet map nao exp cao khong?",
        "Hom nay online ca ngay roi", "Vua thoat nv minh giai tri ti nhe",
        "May cua minh lag qua, cac ban van on chu?"
    );

    private static final List<String> HUNT = Arrays.asList(
        "Quai gan day hoi trau", "Dame minh tam on roi", "Danh bai nay exp kha on",
        "De minh lo con nay", "Chu quai nay danh muoi phat moi chet", "Dame ky hoi sat dien!",
        "Con boss khong biet o dau ta", "Quai hoi sinh nhanh ghe", "Dan dau minh dam con thi phat",
        "May man vua rot do hiem ne", "Lai rot ngoc roi, hom nay den ghi lam",
        "Vua danh con do hiem qua", "Nang cap vu khi xong dame khac la khac",
        "Ai vua danh mat qua quai khong?", "Can them 1 nguoi danh boss nua thoi"
    );

    private static final List<String> REST = Arrays.asList(
        "Doi chut hoi mp", "Het mp roi nghi ti", "Nghi 1 chut roi danh tiep",
        "Cho minh hoi skill xong da", "Xai doi chut dang an com", "Nghi lam roi danh mai the la kho noi",
        "Doi minh uong nuoc cai da", "MP het nhanh that, phai co manh hoa hong",
        "Di ngu thoi, mai danh tiep", "Sap het niem vui roi, nghi da"
    );

    private static final List<String> SOCIAL = Arrays.asList(
        "Ai lap team train khong?", "Cho minh vao nhom voi", "Pt 2 nguoi clear nhanh hon do",
        "Team nao thieu nguoi khong?", "Ban nao roi vao nhom minh di", "Tim them 1 nguoi di task",
        "Bang minh tuyen thanh vien, ai muon tham gia?", "Hoat dong bang sap toi, don dien",
        "Co ai muon lam nv bang cung minh khong?", "Pt nao di dau ta, cho minh theo voi"
    );

    private static final List<String> GREET_FRIEND = Arrays.asList(
        "Ong lai gap roi, train chung nhe!", "Hello ban, bai nay exp ngon lam",
        "Ban khoe khong, lau khong gap", "Gap nhau qua nhe, di chung khong?",
        "Ban tang minh vat pham khong ta?", "Cam on ban hom qua nha",
        "Ban len cap nhanh that do", "Hahaa, ban danh cung minh di"
    );

    private static final List<String> GREET_STRANGER = Arrays.asList(
        "Chao ong, minh moi qua map nay", "Hi, train chung cho vui nhe?",
        "Ban lv may roi ta?", "Ban dung class gi vay?", "Ban o server nao nhi?",
        "Ban co biet map nao rot do khong?", "Ban cho minh xin it kinh nghiem di",
        "Ban khoe khong, gap ho nhu gap nguoi quen", "Hi ban, minh co the hoat dong quanh day"
    );

    private static final List<String> TRADE = Arrays.asList(
        "Ban co ban do khong?", "Do nay minh thieu, ban co thua khong?",
        "Co nguoi ban ngoc khong ta?", "Gia do nay ban bao nhieu vay ban?",
        "Ban co muon giao dich khong?", "Minh co vat pham du, ban can khong?",
        "Nhan do ban hay chuyen shop nhi?", "Ai co vu khi tot ban minh voi"
    );

    private static final List<String> TALK = Arrays.asList(
        "Ban cho minh hoi lam sao tang dame nhanh?",
        "Cac ban cho minh xin kinh nghiem lam nv nhanh",
        "Hom nay thoi tiet nong qua, ban khoe khong?",
        "Minh nghi la map sau se co boss to",
        "Ban thay server nay vui khong?",
        "Do hiem nay minh lam roi, kho qua",
        "Ban thich class nao nhat ta?",
        "Choi lau chac minh chi lam ban voi ban thoi",
        "Nghe noi gan nay co su kien, ban tham gia chua?",
        "Minh chi danh quai khong di boss, so chet lam"
    );

    private static final List<String> EXCLAIM = Arrays.asList(
        "Troi oi!", "Trời ạ!", "Kinh quá!", "Hay quá đi!", "Buồn cười ghê!",
        "Xui thật!", "May qua!", "Chua the tin noi!", "Suong that!", "Ghê vậy!"
    );

    /** Nhóm câu nhắn riêng (theo mẫu NRO VirtualChat nhắn tin riêng). */
    private static final List<String> PRIVATE = Arrays.asList(
        "Ban o dau vay, minh vao khu ban?",
        "Di train chung khong ban?",
        "Cam on ban nha!",
        "Cho minh xin 1 vat pham du duoc khong?",
        "Mai gap nhau nha, minh phai di roi",
        "Ban co nhom khong, cho minh vao voi",
        "Minh co do hay lam, ban xem thu khong?",
        "Ban cho minh xin it xu duoc khong ta?"
    );

    private static String spice(String line) {
        if (NinjaUtils.nextInt(0, 100) < 22) {
            return line + " " + EXCLAIM.get(NinjaUtils.nextInt(0, EXCLAIM.size() - 1));
        }
        return line;
    }

    private static String pick(AutoFarmBot bot, List<String> pool) {
        // Chống lặp: thử tối đa 5 lần lấy câu chưa nói gần đây
        for (int i = 0; i < 5; i++) {
            String l = pool.get(NinjaUtils.nextInt(0, pool.size() - 1));
            if (!bot.botMemory.saidRecently(l)) {
                return l;
            }
        }
        return null;
    }

    public static void tick(AutoFarmBot bot) {
        if (bot == null || bot.zone == null) {
            return;
        }
        long now = System.currentTimeMillis();
        if (now < bot.nextAiChatTime) {
            return;
        }
        float rate = BotConfig.CHAT_RATE * bot.botProfile.talkativeness;
        if (rate <= 0.05f) {
            return;
        }
        bot.nextAiChatTime = now + (long) (NinjaUtils.nextInt(5000, 12000) / Math.max(0.2, rate));
        // Ưu tiên câu tùy chỉnh từ bot_chat.txt (mẫu Anwin VirtualChatConfig)
        try {
            BotChatConfig cfg = BotChatConfig.gI();
            cfg.reloadIfStale();
            String custom = cfg.randomLine();
            if (custom != null && !bot.botMemory.saidRecently(custom)
                    && NinjaUtils.nextInt(0, 100) < 60 * Math.min(1.0, rate)) {
                bot.zone.getService().chat(bot.id, custom);
                bot.botMemory.rememberChat(custom);
                bot.botNeeds.satisfy(BotNeeds.SOCIAL, 0.4);
                return;
            }
        } catch (Exception ignored) {
        }
        String line;
        Char near = BotPerception.nearestRealPlayer(bot, 400);
        if (near != null) {
            String label = bot.botMemory.relationLabel(near.name);
            if ("friend".equals(label)) {
                line = pick(bot, GREET_FRIEND);
            } else {
                line = pick(bot, GREET_STRANGER);
            }
            bot.botMemory.adjustRelation(near.name, 2);
        } else if (bot.hp < bot.maxHP * 0.35) {
            line = pick(bot, REST);
        } else if (bot.botState == BotState.ATTACK || bot.botState == BotState.MOVE_TO_TARGET) {
            line = pick(bot, HUNT);
        } else if (bot.botState == BotState.SOCIAL) {
            line = pick(bot, SOCIAL);
        } else {
            int r = NinjaUtils.nextInt(0, 100);
            if (r < 12) {
                line = pick(bot, TRADE);
            } else if (r < 24) {
                line = pick(bot, TALK);
            } else {
                line = pick(bot, ROAM);
            }
        }
        if (line == null || bot.botMemory.saidRecently(line)) {
            return;
        }
        line = spice(line);
        bot.zone.getService().chat(bot.id, line);
        bot.botMemory.rememberChat(line);
        bot.botNeeds.satisfy(BotNeeds.SOCIAL, 0.3);
    }

    /** Nhắn tin riêng cho người chơi gần (theo mẫu NRO chatPrivate). */
    public static void tickPrivate(AutoFarmBot bot) {
        if (bot == null || bot.zone == null) {
            return;
        }
        long now = System.currentTimeMillis();
        if (now < bot.nextAiPrivateTime) {
            return;
        }
        bot.nextAiPrivateTime = now + NinjaUtils.nextInt(45000, 120000);
        Char near = BotPerception.nearestRealPlayer(bot, 300);
        if (near == null) {
            return;
        }
        // Chỉ nhắn riêng khi đã quen (relation >= 5) để giống người thật
        if (bot.botMemory.relation(near.name) < 5) {
            return;
        }
        try {
            String line = pick(bot, PRIVATE);
            if (line != null) {
                near.getService().chat(bot.name, line);
                bot.botMemory.rememberChat(line);
                bot.botNeeds.satisfy(BotNeeds.SOCIAL, 0.5);
            }
        } catch (Exception e) {
            Log.error("BotChat private err: " + e.getMessage(), e);
        }
    }

    /** Chào hỏi riêng khi vừa kết bạn (gọi sau khi addFriend thành công). */
    public static void greetNewFriend(AutoFarmBot bot, String friendName) {
        if (bot == null || friendName == null) {
            return;
        }
        try {
            Char c = Exe_Z.server.ServerManager.findCharByName(friendName);
            if (c != null && c.getService() != null) {
                c.getService().chat(bot.name, "Cam on ban da ket ban! Khi nao ranh di train chung nha!");
            }
        } catch (Exception ignored) {
        }
    }
}
