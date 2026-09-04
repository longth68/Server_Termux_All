package nro.bot.ai;

import nro.bot.Bot;
import Utils.Util;

public class BotBrain {
    private Bot bot;
    private BotState currentState;
    private BotProfile profile;
    private BotMemory memory;
    
    private nro.bot.ai.components.BotMovement movement;
    private nro.bot.ai.components.BotCombat combat;
    private nro.bot.ai.components.BotInventory inventory;
    private nro.bot.ai.components.BotChat chat;
    private nro.bot.ai.components.BotPerception perception;
    private nro.bot.ai.components.BotDecision decision;
    private nro.bot.ai.components.BotParty party;
    
    private long lastTimeThink;
    private long lastTimeAction;
    private long timeSpawned;

    public BotBrain(Bot bot) {
        this.bot = bot;
        this.currentState = BotState.IDLE;
        this.profile = new BotProfile(bot.name);
        this.memory = new BotMemory();
        
        this.movement = new nro.bot.ai.components.BotMovement(bot, this);
        this.combat = new nro.bot.ai.components.BotCombat(bot, this);
        this.inventory = new nro.bot.ai.components.BotInventory(bot, this);
        this.chat = new nro.bot.ai.components.BotChat(bot, this);
        this.perception = new nro.bot.ai.components.BotPerception(bot, this);
        this.decision = new nro.bot.ai.components.BotDecision(bot, this, this.perception);
        this.party = new nro.bot.ai.components.BotParty(bot, this);
        
        this.timeSpawned = System.currentTimeMillis();
    }

    public void update() {
        if (bot == null) return;

        // Nếu đang ngủ đông hoặc đã offline
        if (currentState == BotState.OFFLINE) {
            return;
        }

        // Kiểm tra xem đã đến giờ nghỉ ngơi chưa (Mô phỏng người chơi out game)
        if (System.currentTimeMillis() - timeSpawned > profile.onlineDuration) {
            currentState = BotState.OFFLINE;
            nro.services.Fun.ChangeMapService.gI().exitMap(bot);
            System.out.println("[Bot AI] Bot " + bot.name + " đã hết giờ chơi, tiến hành offline.");
            return;
        }

        // Tạo độ trễ (delay) trước khi hành động tiếp theo
        if (System.currentTimeMillis() - lastTimeAction < profile.reactionDelay) {
            return;
        }

        // Nếu Bot chết
        if (bot.isDie()) {
            changeState(BotState.DEAD);
        }

        // Việc kiểm tra máu và dùng đậu đã được chuyển vào State HEAL

        // Background Simulation: Nếu map không có người chơi thật nào, giảm tần suất update để tiết kiệm CPU
        boolean hasRealPlayer = false;
        if (bot.zone != null) {
            for (nro.player.Player pl : bot.zone.getPlayers()) {
                if (!pl.isBot && !pl.isBoss && !pl.isDeTu) {
                    hasRealPlayer = true;
                    break;
                }
            }
        }
        
        if (!hasRealPlayer) {
            // Background sim: Thỉnh thoảng tick 1 lần để nhặt đồ, hoặc đi tìm khu có người chơi thật
            if (System.currentTimeMillis() - lastTimeAction < 3000) {
                return; 
            }
            
            // Logic: Đi tìm người chơi thật (giới hạn 5 bot/khu)
            if (Utils.Util.isTrue(30, 100)) { // 30% cơ hội mỗi 3 giây
                for (nro.player.Player realPl : nro.server.Client.gI().getPlayers()) {
                    if (realPl != null && !realPl.isBot && !realPl.isBoss && !realPl.isDeTu && realPl.zone != null && realPl.zone.map.mapId != 51) {
                        // Kiểm tra khu vực này có bao nhiêu Bot rồi
                        long botCount = realPl.zone.getPlayers().stream().filter(p -> p.isBot && p.id != bot.id).count();
                        if (botCount < 5) {
                            // Teleport đến khu này
                            bot.location.x = realPl.location.x + Utils.Util.nextInt(-50, 50);
                            bot.location.y = realPl.location.y;
                            nro.services.Fun.ChangeMapService.gI().changeMapYardrat(bot, realPl.zone, bot.location.x, bot.location.y);
                            lastTimeAction = System.currentTimeMillis();
                            return;
                        }
                    }
                }
            }
            
            lastTimeAction = System.currentTimeMillis();
        }

        // Cập nhật chat
        if (chat != null && hasRealPlayer) {
            chat.updateChat();
        }
        
        // Cập nhật Party Follow
        if (party != null) {
            party.update();
        }

        think();
    }

    private void think() {
        if (System.currentTimeMillis() - lastTimeThink < profile.thinkDelay) {
            return; // Đang "suy nghĩ", không đổi state liên tục
        }
        lastTimeThink = System.currentTimeMillis();
        
        // Quét xung quanh
        if (perception != null) {
            perception.scan();
        }

        // Việc nhặt đồ đã được chuyển vào State PICK_ITEM để đồng bộ với Máy Trạng Thái
        
        // Đánh giá trạng thái tiếp theo thông qua Utility AI nếu đang rảnh
        if (currentState == BotState.IDLE || currentState == BotState.FIND_TARGET) {
            BotState nextState = decision.evaluateNextState();
            changeState(nextState);
        }

        switch (currentState) {
            case IDLE:
                handleIdle();
                break;
            case FIND_TARGET:
                handleFindTarget();
                break;
            case MOVE_TO_TARGET:
                handleMoveToTarget();
                break;
            case ATTACK:
                handleAttack();
                break;
            case DEAD:
                handleDead();
                break;
            case RESPAWN:
                handleRespawn();
                break;
            case HEAL:
                handleHeal();
                break;
            case PICK_ITEM:
                handlePickItem();
                break;
            // Các trạng thái quest sẽ tích hợp ở Phase sau
            default:
                // Nếu chưa có state nào phù hợp, quay về IDLE
                changeState(BotState.IDLE);
                break;
        }
    }

    private void handleIdle() {
        if (bot.zone == null) {
            changeState(BotState.SPAWN);
            bot.joinMap();
        } else {
            // Chạy lang thang
            movement.wander();
            lastTimeAction = System.currentTimeMillis();
        }
    }

    private void handleFindTarget() {
        combat.findTarget();
        lastTimeAction = System.currentTimeMillis();
    }

    private void handleMoveToTarget() {
        nro.mob.Mob target = combat.getTargetMob();
        if (target == null || target.isDie()) {
            changeState(BotState.FIND_TARGET);
            return;
        }

        int distance = Utils.Util.getDistance(bot, target);
        if (distance <= profile.attackRange) {
            changeState(BotState.ATTACK);
        } else {
            movement.moveToTarget(target.location.x, target.location.y);
            lastTimeAction = System.currentTimeMillis();
        }
    }

    private void handleAttack() {
        combat.attackTarget();
        lastTimeAction = System.currentTimeMillis();
    }

    private void handleDead() {
        // Đợi 1 chút rồi chuyển sang RESPAWN
        if (System.currentTimeMillis() - lastTimeAction > 5000) {
            changeState(BotState.RESPAWN);
        }
    }

    private void handleRespawn() {
        // Gọi lệnh hồi sinh
        nro.services.Service.gI().hsChar(bot, bot.nPoint.hpMax, bot.nPoint.mpMax);
        changeState(BotState.IDLE);
        lastTimeAction = System.currentTimeMillis();
    }

    private void handleHeal() {
        if (inventory != null) {
            inventory.checkAndHeal();
        }
        changeState(BotState.IDLE);
        lastTimeAction = System.currentTimeMillis();
    }

    private void handlePickItem() {
        if (inventory != null) {
            inventory.checkAndPickItem();
        }
        changeState(BotState.IDLE);
        lastTimeAction = System.currentTimeMillis();
    }

    public void changeState(BotState newState) {
        if (this.currentState != newState) {
            this.currentState = newState;
            // System.out.println("[Bot AI] Bot " + bot.name + " changed state to: " + newState);
        }
    }

    public BotState getCurrentState() {
        return currentState;
    }

    public BotProfile getProfile() {
        return profile;
    }

    public BotMemory getMemory() {
        return memory;
    }

}
