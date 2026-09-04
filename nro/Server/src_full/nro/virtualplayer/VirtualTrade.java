package nro.virtualplayer;

import Utils.Util;
import java.util.List;
import models.Item.Item;
import nro.player.Player;
import nro.services.ChatGlobalService;
import nro.services.Service;
import nro.virtualplayer.chat.ChatIntentClassifier;
import nro.virtualplayer.chat.ChatIntentClassifier.ClassificationResult;
import nro.virtualplayer.chat.ChatIntentClassifier.Intent;
import nro.virtualplayer.chat.ConversationStateMachine;
import nro.virtualplayer.chat.ConversationStateMachine.ConversationContext;
import nro.virtualplayer.chat.ConversationStateMachine.State;
import nro.virtualplayer.core.VirtualPersonality;

/**
 * Port tu Hashirama: bot rao ban / mac ca / tra loi theo y dinh chat.
 * Khac biet: khong tao thread "VP-Reply" rieng - tin nhan cho duoc dua vao
 * hang doi pendingReplies cua bot va Brain.update() giao dan (deliverPending).
 */
public class VirtualTrade {

    private static final String[] ADVERTISE_LINES = {
        "Bán %s giá %s vàng, ai cần nhắn em",
        "Ai mua %s không? Giá mềm %s",
        "%s x%d rao giá %s, pm em để trao đổi",
        "Thanh lý %s, giá %s. Mua sớm kẻo hết",
        "Có %s bán giá %s, ai quan tâm thì pm",
        "Rao %s giá %s, ai cần thì liên hệ",
        "%s bán giá %s, mua nhanh kẻo ai lấy mất",
        "Ai cần %s không? Giá %s thôi, rẻ lắm",
        "Bán %s giá hời %s, shop online 24/7",
        "%s giá %s, deal trực tiếp ai cần pm ngay",
        "Mình bán %s giá %s, cam kết giá tốt nhất server",
        "Xả kho: %s chỉ %s, ai nhanh ai được",
        "%s đang hot nha, giá %s, mua sớm có hàng",
        "Ai muốn mua %s không? %s là chốt nhé",
        "Endgame item: %s giá %s, thanh lý cuối tháng"
    };

    private static final String[] HAGGLE_GREEDY = {
        "Bớt không được đâu %s, %s là cố định rồi",
        "Không bớt được %s ơi, giá này là rẻ nhất rồi",
        "%s ơi, giá này tao đã lỗ rồi, bớt sao nổi",
        "Nghĩa là %s muốn tao lỗ à? %s là chốt",
        "Không deal được %s ơi, %s là cuối cùng",
        "Giá này tao đã bán lỗ rồi, bớt sao nổi nữa"
    };

    private static final String[] HAGGLE_HELPFUL = {
        "Thôi được %s, giảm còn %s cho anh em đẹp lòng",
        "Được %s, %s nhé, ưu ái đặc biệt",
        "%s giảm cho %s, nhưng giữ bí mật nhé",
        "Vì %s nên giảm còn %s, lần sau không có nữa",
        "Chịu %s, %s cho vui, nhưng lần sau giá bình thường"
    };

    private static final String[] HAGGLE_NORMAL = {
        "Bớt chút thôi: %s cuối nhé",
        "Được %s, nhưng lần sau giá bình thường đó",
        "%s nha %s, cuối cùng rồi đó",
        "Bớt %s được không? Thôi %s vậy",
        "Bớt được tí %s, nhưng %s là chốt"
    };

    private static final String[] ACCEPT_TRADE = {
        "Ok %s, gặp em ở map hiện tại rồi giao dịch nhé. Giá chốt %s",
        "Deal %s! %s vàng, đến map này gặp em",
        "Đồng ý %s, %s là deal nha. Gặp ở đây",
        "Chốt %s vàng %s, em đang ở map này, đến liền",
        "Chốt %s vàng %s, em đợi ở map hiện tại",
        "Ok %s, %s là deal nhé. Em ở map này"
    };

    private static final String[] REPLY_AD = {
        "%s giá %s đó %s, thích thì nhắn giá của cậu",
        "Đây %s giá %s đó %s, mua thì pm",
        "%s bán %s thôi %s, rất hời đó",
        "%s giá %s, %s muốn thì trao đổi luôn",
        "%s đang rao giá %s %s, ai quan tâm thì pm"
    };

    private static final String[] GREET_GREETING = {
        "Chào %s, cần gì cứ nói",
        "Hello %s, em đang bán đồ nè",
        "Hi %s, có gì cần thì gọi em",
        "Chào %s, xem hàng gì không?",
        "Hà %s, em có nhiều item ngon lắm"
    };

    private static final String[] GREET_QUIET = {
        "Ừ chào",
        "...",
        "Hi",
        "Hm",
        "Ok"
    };

    private static final String[] PARTY_ACCEPT = {
        "Ok %s, mời tao đi, tao vào nhóm cho",
        "Được %s, vào nhóm đi",
        "%s ơi, mời nhóm đi, tao theo",
        "Ok %s, team lên nào"
    };

    private static final String[] PARTY_REJECT = {
        "Thôi %s, tao đang bận",
        "Không rảnh %s ơi, để hôm khác",
        "%s, để tao train xong đã",
        "Sorry %s, để lúc khác nhé"
    };

    private static final String[] BOSS_ACCEPT = {
        "Boss nào? %s dẫn tao đi, tao đánh phụ",
        "Ok %s, đi boss luôn, chỉ đường đi",
        "Được %s, tao cũng đang rảnh, đi boss thôi",
        "%s ơi, đi boss đi, tao cần loot"
    };

    private static final String[] BOSS_REJECT = {
        "Boss yếu quá %s ơi, tao không ham",
        "%s, boss này tao đánh rồi, loot dở lắm",
        "Thôi %s, boss khó quá, tao yếu"
    };

    private static final String[] PK_ACCEPT = {
        "%s PK hả? Ra map 13 đi, tao đợi",
        "Được %s, đấu luôn không chần chừ",
        "%s muốn PK? Được thôi, map nào?",
        "Ok %s, tỷ thí luôn, mày đợi tao"
    };

    private static final String[] HELP_LINES = {
        "%s cần giúp gì? Nói tao đi",
        "Có gì cứ hỏi %s, tao biết sẽ chỉ",
        "%s ơi, cần hướng dẫn gì không?",
        "Để tao giúp %s, có gì cứ nói"
    };

    private static final String[] UNKNOWN_LINES = {
        "Hử %s? Tao không hiểu, nói lại đi",
        "%s nói gì vậy? Tao chưa kịp đọc",
        "Gì cơ %s? Nói rõ hơn được không",
        "%s ơi, nói chậm thôi, tao đang train"
    };

    private static final ConversationStateMachine STATE_MACHINE = new ConversationStateMachine();

    /** Tin nhan rieng cho giao dan trong tick chinh. */
    public static class PendingReply {
        public final Player sender;
        public final String text;
        public final long deliverAt;
        public PendingReply(Player sender, String text, long deliverAt) {
            this.sender = sender;
            this.text = text;
            this.deliverAt = deliverAt;
        }
    }

    public static void advertise(VirtualPlayer vp) {
        try {
            if (vp == null || vp.inventory == null) return;
            Item it = pickSellableItem(vp);
            if (it == null || it.template == null || it.template.id == -1) {
                return;
            }
            long base = Math.max(100_000L, (long) it.template.strRequire * Util.nextInt(2_000, 9_000)
                    + it.quantity * 10_000L);
            if (vp.profile.hasPersonality(VirtualPersonality.GREEDY)) {
                base *= 2;
            }
            vp.adItemName = it.template.name;
            vp.adPrice = base;
            String priceStr = Util.numberToMoney(base);
            String line;
            int style = Util.nextInt(0, ADVERTISE_LINES.length - 1);
            line = String.format(ADVERTISE_LINES[style], it.template.name, priceStr);
            if (ADVERTISE_LINES[style].contains("%d")) {
                line = String.format(ADVERTISE_LINES[style], it.template.name, it.quantity, priceStr);
            }
            ChatGlobalService.gI().chat(vp, line);
        } catch (Exception ignored) {
        }
    }

    private static Item pickSellableItem(VirtualPlayer vp) {
        try {
            for (int i = 0; i < 6; i++) {
                boolean fromBag = Util.isTrue(60, 100);
                List<Item> list = fromBag ? vp.inventory.itemsBag : vp.inventory.itemsBody;
                if (list == null || list.isEmpty()) {
                    continue;
                }
                Item it = list.get(Util.nextInt(0, list.size() - 1));
                if (it != null && it.template != null && it.template.id != -1
                        && it.itemOptions != null && !it.itemOptions.isEmpty()) {
                    return it;
                }
            }
        } catch (Exception ignored) {
        }
        return null;
    }

    /** Goi tu Service.chat: player that chat trong map -> bot cung map phan hoi. */
    public static void onPlayerChat(Player sender, String text) {
        try {
            if (sender == null || text == null || text.isEmpty()) return;
            if (sender.isBot || sender.isBoss || sender.isDeTu || !sender.isPlayer) return;
            if (sender.zone == null) return;
            for (VirtualPlayer bot : VirtualPlayerManager.gI().getBots()) {
                try {
                    if (bot == null || !bot.active || bot.zone == null) continue;
                    if (bot.zone != sender.zone) continue;
                    long now = System.currentTimeMillis();
                    if (now - bot.lastTradeReply < 5000) continue;
                    bot.lastTradeReply = now;
                    onPlayerMessage(sender, bot, text);
                } catch (Exception ignored) {}
            }
        } catch (Exception ignored) {}
    }

    public static void onPlayerMessage(Player sender, VirtualPlayer bot, String text) {
        if (sender == null || bot == null || text == null || text.isEmpty()) {
            return;
        }
        try {
            ClassificationResult cr = ChatIntentClassifier.classify(text);
            ConversationContext ctx = STATE_MACHINE.getOrCreate(sender.name);
            ctx.recordMessage(sender.name, text);
            bot.memory.adjustRelation(sender.name, 5);
            String reply = buildReply(bot, sender, text, cr, ctx);
            if (reply == null || reply.isEmpty()) {
                return;
            }
            ctx.recordBotMessage(sender.name, reply);
            long delay = bot.profile.reactionDelay + Util.nextInt(400, 2200);
            bot.pendingReplies.offer(new PendingReply(sender, reply, System.currentTimeMillis() + delay));
        } catch (Exception ignored) {
        }
    }

    /** Giao tin nhan cho trong tick chinh (goi tu VirtualBrain.update). */
    public static void deliverPending(VirtualPlayer vp) {
        try {
            if (vp == null) return;
            int n = 0;
            while (n < 2) {
                PendingReply pr = vp.pendingReplies.peek();
                if (pr == null || pr.deliverAt > System.currentTimeMillis()) break;
                vp.pendingReplies.poll();
                n++;
                try {
                    if (pr.sender != null && pr.sender.zone != null) {
                        Service.gI().chatPrivate(vp, pr.sender, pr.text);
                    }
                } catch (Exception ignored) {}
            }
        } catch (Exception ignored) {}
    }

    private static String buildReply(VirtualPlayer bot, Player sender, String raw, ClassificationResult cr, ConversationContext ctx) {
        String low = cr.normalized;
        String itemName = bot.adItemName == null ? "" : bot.adItemName.toLowerCase();
        boolean askingMyAd = !itemName.isEmpty() && (low.contains(itemName.split(" ")[0]) || low.contains(itemName));
        boolean tradeContext = ctx.state == State.AWAITING_PRICE || ctx.state == State.HAGGLING || ctx.state == State.CONFIRMING_TRADE;
        switch (cr.intent) {
            case BUY:
            case SELL:
            case HAGGLE:
            case TRADE_ACCEPT:
            case TRADE_REJECT:
                return handleTradeIntent(bot, sender, low, cr, ctx, askingMyAd, tradeContext);
            case GREETING:
                return handleGreeting(bot, sender, low, ctx);
            case PK:
                return handlePk(bot, sender, low, ctx);
            case BOSS:
                return handleBoss(bot, sender, low, ctx);
            case PARTY:
                return handleParty(bot, sender, low, ctx);
            case HELP:
                return handleHelp(bot, sender, low, ctx);
            case BEG:
                return handleBeg(bot, sender, low, ctx);
            case CLAN:
                return handleClan(bot, sender, low, ctx);
            case LEVEL:
                return handleLevel(bot, sender, low, ctx);
            case QUEST:
                return "Nhiệm vụ hả? Tao cũng đang làm dở, để xong đã";
            case INFO:
                return String.format("Server này vui lắm %s, cày là lên level", sender.name);
            case FRIEND_REQUEST:
                return String.format("Kết bạn hả %s? Ừ được, add tao đi", sender.name);
            case EMOTE:
                return handleEmote(bot, sender, low, ctx);
            default:
                return handleUnknown(bot, sender, low, ctx);
        }
    }

    private static String handleTradeIntent(VirtualPlayer bot, Player sender, String low, ClassificationResult cr, ConversationContext ctx, boolean askingMyAd, boolean tradeContext) {
        String itemName = bot.adItemName == null ? "" : bot.adItemName.toLowerCase();
        long price = bot.adPrice > 0 ? bot.adPrice : 50_000_000L;

        if (cr.intent == Intent.HAGGLE || (tradeContext && (low.contains("rẻ") || low.contains("bớt") || low.contains("giảm")))) {
            ctx.state = State.HAGGLING;
            ctx.offeredPrice = price;
            bot.memory.adjustRelation(sender.name, -2);
            if (bot.profile.hasPersonality(VirtualPersonality.GREEDY)) {
                return String.format(HAGGLE_GREEDY[Util.nextInt(0, HAGGLE_GREEDY.length - 1)],
                        sender.name, Util.numberToMoney(price));
            }
            if (bot.profile.hasPersonality(VirtualPersonality.HELPFUL)) {
                long cut = price / 10;
                bot.adPrice = price - cut;
                ctx.counterPrice = bot.adPrice;
                return String.format(HAGGLE_HELPFUL[Util.nextInt(0, HAGGLE_HELPFUL.length - 1)],
                        sender.name, Util.numberToMoney(bot.adPrice));
            }
            long cut = price / 20;
            bot.adPrice = price - cut;
            ctx.counterPrice = bot.adPrice;
            return String.format(HAGGLE_NORMAL[Util.nextInt(0, HAGGLE_NORMAL.length - 1)],
                    Util.numberToMoney(bot.adPrice), sender.name);
        }

        if (cr.intent == Intent.TRADE_ACCEPT || (tradeContext && (low.contains("ok") || low.contains("mua") || low.contains("chốt") || low.contains("đồng ý")))) {
            ctx.state = State.TRADE_COMPLETE;
            ctx.exchangeCount++;
            bot.memory.adjustRelation(sender.name, 10);
            return String.format(ACCEPT_TRADE[Util.nextInt(0, ACCEPT_TRADE.length - 1)],
                    sender.name, Util.numberToMoney(bot.adPrice > 0 ? bot.adPrice : price));
        }

        if (cr.intent == Intent.TRADE_REJECT) {
            ctx.state = State.IDLE;
            bot.memory.adjustRelation(sender.name, -5);
            return String.format("Thôi không sao %s, để hôm khác", sender.name);
        }

        if (askingMyAd || bot.adItemName != null) {
            ctx.state = State.AWAITING_PRICE;
            ctx.requestedItem = bot.adItemName;
            ctx.offeredPrice = price;
            return String.format(REPLY_AD[Util.nextInt(0, REPLY_AD.length - 1)],
                    bot.adItemName, Util.numberToMoney(price), sender.name);
        }

        if (tradeContext && ctx.state == State.AWAITING_PRICE) {
            return String.format(REPLY_AD[Util.nextInt(0, REPLY_AD.length - 1)],
                    ctx.requestedItem != null ? ctx.requestedItem : "món này",
                    Util.numberToMoney(ctx.offeredPrice > 0 ? ctx.offeredPrice : price), sender.name);
        }

        if (Util.isTrue(40, 100)) {
            advertise(bot);
            return "Hôm nay em đang bán " + (bot.adItemName == null ? "vài món" : bot.adItemName)
                    + (bot.adPrice > 0 ? " giá " + Util.numberToMoney(bot.adPrice) : "") + ", xem thử đi";
        }
        return "Để xem trong túi đã...";
    }

    private static String handleGreeting(VirtualPlayer bot, Player sender, String low, ConversationContext ctx) {
        ctx.state = State.IDLE;
        ctx.refresh();
        if (bot.profile.getTalkativeness() > 0.4f) {
            return String.format(GREET_GREETING[Util.nextInt(0, GREET_GREETING.length - 1)],
                    sender.name);
        }
        return GREET_QUIET[Util.nextInt(0, GREET_QUIET.length - 1)];
    }

    private static String handlePk(VirtualPlayer bot, Player sender, String low, ConversationContext ctx) {
        ctx.state = State.DISCUSSING_PK;
        ctx.refresh();
        bot.memory.adjustRelation(sender.name, -5);
        if (bot.profile.hasPersonality(VirtualPersonality.RISK_TAKER)
                || bot.profile.hasPersonality(VirtualPersonality.COMPETITIVE)) {
            return String.format(PK_ACCEPT[Util.nextInt(0, PK_ACCEPT.length - 1)], sender.name);
        }
        if (bot.profile.hasPersonality(VirtualPersonality.CAUTIOUS)) {
            return String.format("PK hả %s? Tao yếu lắm, thôi nhé", sender.name);
        }
        return String.format("Không PK đâu %s, mình cày level thôi", sender.name);
    }

    private static String handleBoss(VirtualPlayer bot, Player sender, String low, ConversationContext ctx) {
        ctx.state = State.DISCUSSING_BOSS;
        ctx.refresh();
        if (bot.profile.hasPersonality(VirtualPersonality.RISK_TAKER)
                || bot.profile.hasPersonality(VirtualPersonality.COMPETITIVE)) {
            return String.format(BOSS_ACCEPT[Util.nextInt(0, BOSS_ACCEPT.length - 1)], sender.name);
        }
        if (bot.profile.hasPersonality(VirtualPersonality.CAUTIOUS)) {
            return String.format(BOSS_REJECT[Util.nextInt(0, BOSS_REJECT.length - 1)], sender.name);
        }
        return "Boss nào? Mình đi cùng được không?";
    }

    private static String handleParty(VirtualPlayer bot, Player sender, String low, ConversationContext ctx) {
        ctx.state = State.DISCUSSING_PARTY;
        ctx.refresh();
        if (bot.profile.hasPersonality(VirtualPersonality.SOLO)) {
            return String.format(PARTY_REJECT[Util.nextInt(0, PARTY_REJECT.length - 1)], sender.name);
        }
        if (bot.profile.hasPersonality(VirtualPersonality.HELPFUL)
                || bot.profile.hasPersonality(VirtualPersonality.SOCIAL)) {
            return String.format(PARTY_ACCEPT[Util.nextInt(0, PARTY_ACCEPT.length - 1)], sender.name);
        }
        if (Util.isTrue(50, 100)) {
            return String.format(PARTY_ACCEPT[Util.nextInt(0, PARTY_ACCEPT.length - 1)], sender.name);
        }
        return String.format(PARTY_REJECT[Util.nextInt(0, PARTY_REJECT.length - 1)], sender.name);
    }

    private static String handleHelp(VirtualPlayer bot, Player sender, String low, ConversationContext ctx) {
        ctx.state = State.HELPING;
        ctx.refresh();
        bot.memory.adjustRelation(sender.name, 8);
        if (bot.profile.hasPersonality(VirtualPersonality.HELPFUL)) {
            return String.format(HELP_LINES[Util.nextInt(0, HELP_LINES.length - 1)], sender.name);
        }
        if (bot.profile.hasPersonality(VirtualPersonality.QUIET)) {
            return String.format("Hỏi người khác đi %s, tao cũng không biết", sender.name);
        }
        return String.format("Có gì cứ hỏi %s, tao biết sẽ chỉ", sender.name);
    }

    private static String handleBeg(VirtualPlayer bot, Player sender, String low, ConversationContext ctx) {
        bot.memory.adjustRelation(sender.name, -3);
        if (bot.profile.hasPersonality(VirtualPersonality.HELPFUL)) {
            return "Muốn free hả? Cứ train lên level cao rồi tự có";
        }
        if (bot.profile.hasPersonality(VirtualPersonality.GREEDY)) {
            return String.format("Không có free đâu %s, cày đi", sender.name);
        }
        if (Util.isTrue(20, 100)) {
            return String.format("Thôi được %s, tao cho ít đồ cùi, cố gắng lên", sender.name);
        }
        return String.format("Không có free đâu %s, cày đi", sender.name);
    }

    private static String handleClan(VirtualPlayer bot, Player sender, String low, ConversationContext ctx) {
        ctx.state = State.DISCUSSING_CLAN;
        ctx.refresh();
        if (bot.clan != null) {
            return String.format("Mình đang ở clan %s, vui lắm %s ơi", bot.clan.name, sender.name);
        }
        return "Mình chưa vào clan nào, đang tìm clan";
    }

    private static String handleLevel(VirtualPlayer bot, Player sender, String low, ConversationContext ctx) {
        return String.format("Power %d rồi đó %s, train nhiều vào", bot.nPoint.power, sender.name);
    }

    private static String handleEmote(VirtualPlayer bot, Player sender, String low, ConversationContext ctx) {
        if (low.contains("cười") || low.contains("haha") || low.contains("lol") || low.contains(":)")) {
            return String.format("Hihi %s, vui nhỉ", sender.name);
        }
        if (low.contains("khóc") || low.contains("huhu") || low.contains(":(")) {
            return String.format("Sao thế %s? Có chuyện gì à?", sender.name);
        }
        return String.format("%s nói gì mà vui thế?", sender.name);
    }

    private static String handleUnknown(VirtualPlayer bot, Player sender, String low, ConversationContext ctx) {
        return String.format(UNKNOWN_LINES[Util.nextInt(0, UNKNOWN_LINES.length - 1)], sender.name);
    }
}
