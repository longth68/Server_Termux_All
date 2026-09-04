package nro.bot.ai.components;

import nro.bot.Bot;
import nro.bot.ai.BotBrain;
import nro.bot.ai.BotState;
import Utils.Util;
import nro.mob.Mob;
import nro.player.Player;
import nro.skill.Skill;
import nro.skill.SkillService;

public class BotCombat {
    private Bot bot;
    private BotBrain brain;
    private Mob targetMob;

    public BotCombat(Bot bot, BotBrain brain) {
        this.bot = bot;
        this.brain = brain;
    }

    public void findTarget() {
        if (bot.zone == null || bot.zone.mobs.isEmpty()) {
            return;
        }

        // Tìm một quái vật hợp lệ (còn sống)
        for (Mob mob : bot.zone.mobs) {
            if (!mob.isDie()) {
                // Kiểm tra xem quái có đang bị người chơi khác đánh hay nhắm tới không
                boolean isReserved = false;
                for (Player pl : brain.getMemory().playerRelationships.keySet().isEmpty() ? new java.util.ArrayList<Player>() : bot.zone.getPlayers()) {
                     // Nếu có tính năng target
                }
                // Tạm thời đơn giản: check xem có ai đang đứng sát quái không
                if (bot.zone.getPlayers().stream().anyMatch(p -> !p.isBot && !p.isDeTu && !p.isBoss && Utils.Util.getDistance(p.location.x, p.location.y, mob.location.x, mob.location.y) < 60)) {
                    isReserved = true; // Nhường quái
                }
                
                if (!isReserved || brain.getProfile().isAggressive && Utils.Util.isTrue(30, 100)) {
                    this.targetMob = mob;
                    brain.changeState(BotState.MOVE_TO_TARGET);
                    return;
                }
            }
        }
        
        // Không tìm thấy quái, đi lang thang
        brain.changeState(BotState.IDLE);
    }

    public void attackTarget() {
        if (targetMob == null || targetMob.isDie()) {
            this.targetMob = null;
            brain.changeState(BotState.FIND_TARGET);
            return;
        }

        int distance = Util.getDistance(bot, targetMob);

        if (distance > brain.getProfile().attackRange) {
            brain.changeState(BotState.MOVE_TO_TARGET);
            return;
        }

        // Chọn skill
        if (!bot.playerSkill.skills.isEmpty()) {
            if (Util.isTrue(90, 100) || bot.playerSkill.skills.size() == 1) {
                bot.playerSkill.skillSelect = bot.playerSkill.skills.get(0);
            } else {
                bot.playerSkill.skillSelect = bot.playerSkill.skills.get(1);
            }
        }

        // Tấn công bằng SkillService giống hệt player
        if (bot.playerSkill.skillSelect != null) {
            SkillService.gI().useSkill(bot, null, targetMob, -1, null);
        }
    }

    public Mob getTargetMob() {
        return targetMob;
    }
}
