package nro.virtualplayer.chat;

import java.util.HashMap;
import java.util.Map;

public class ConversationStateMachine {

    public enum State {
        IDLE,
        AWAITING_PRICE,
        HAGGLING,
        CONFIRMING_TRADE,
        TRADE_COMPLETE,
        DISCUSSING_PARTY,
        DISCUSSING_BOSS,
        DISCUSSING_PK,
        DISCUSSING_CLAN,
        HELPING,
        AWAITING_CONFIRMATION
    }

    public static class ConversationContext {
        public State state = State.IDLE;
        public long lastActivity = System.currentTimeMillis();
        public String requestedItem;
        public long offeredPrice;
        public long counterPrice;
        public int exchangeCount;
        public String lastBotMessage;
        public String lastPlayerMessage;

        public boolean isExpired() {
            return System.currentTimeMillis() - lastActivity > 300_000L;
        }

        public void refresh() {
            lastActivity = System.currentTimeMillis();
        }

        public void reset() {
            state = State.IDLE;
            requestedItem = null;
            offeredPrice = 0;
            counterPrice = 0;
            exchangeCount = 0;
            lastBotMessage = null;
            lastPlayerMessage = null;
            refresh();
        }

        public void recordMessage(String playerName, String message) {
            this.lastPlayerMessage = message;
            refresh();
        }

        public void recordBotMessage(String playerName, String message) {
            this.lastBotMessage = message;
            refresh();
        }
    }

    private final Map<String, ConversationContext> conversations = new HashMap<>();

    public ConversationContext getOrCreate(String playerName) {
        ConversationContext ctx = conversations.get(playerName);
        if (ctx == null) {
            ctx = new ConversationContext();
            conversations.put(playerName, ctx);
        }
        if (ctx.isExpired() && ctx.state != State.IDLE) {
            ctx.reset();
        }
        return ctx;
    }

    public ConversationContext get(String playerName) {
        ConversationContext ctx = conversations.get(playerName);
        if (ctx != null && ctx.isExpired() && ctx.state != State.IDLE) {
            ctx.reset();
        }
        return ctx;
    }

    public void setState(String playerName, State state) {
        ConversationContext ctx = getOrCreate(playerName);
        ctx.state = state;
        ctx.refresh();
    }

    public void setState(String playerName, State state, long price) {
        ConversationContext ctx = getOrCreate(playerName);
        ctx.state = state;
        ctx.offeredPrice = price;
        ctx.refresh();
    }

    public void recordMessage(String playerName, String message) {
        ConversationContext ctx = getOrCreate(playerName);
        ctx.lastPlayerMessage = message;
        ctx.refresh();
    }

    public void recordBotMessage(String playerName, String message) {
        ConversationContext ctx = getOrCreate(playerName);
        ctx.lastBotMessage = message;
        ctx.refresh();
    }

    public void cleanup() {
        conversations.entrySet().removeIf(e -> e.getValue().isExpired());
    }

    public int activeCount() {
        cleanup();
        return conversations.size();
    }
}