package Exe_Z.bot.ai;

import Exe_Z.bot.AutoFarmBot;
import Exe_Z.item.Item;
import Exe_Z.util.Log;
import Exe_Z.util.NinjaUtils;

/**
 * Nâng cấp toàn diện BOT: làm nhiệm vụ, nâng cấp đồ, nhặt đồ hiếm,
 * chết/hồi sinh — phản ứng chat theo từng sự kiện như người chơi thật.
 * Tái dùng API game có sẵn: updateTaskCount/skipTask/takingTask, tickUpgrade,
 * aiPickup. Toàn bộ OFFLINE, không API.
 */
public class BotProgress {

    /** Cooldown tick nhiệm vụ per-bot (dùng botTick chia Phase). */
    private static final int TASK_TICK_DIV = 240; // ~2 phút / lần xử lý NV

    // ================= NHIỆM VỤ =================

    /**
     * Bot tự làm nhiệm vụ chính tuyến:
     * - Chưa nhận NV -> nhận (takingTask)
     * - Đang làm: đếm tiến độ theo hành vi thật (đánh quái +1 như kill quest),
     *   đạt chỉ số -> taskNext -> updateTask (sang NV mới)
     * - Kẹt quá lâu -> bỏ qua 1 NV (giống người chơi bỏ cuốc)
     */
    public static void tickTask(AutoFarmBot bot) {
        if (bot == null || !BotConfig.ENABLED) {
            return;
        }
        try {
            // Nhận nhiệm vụ nếu chưa có
            if (bot.taskMain == null && bot.taskId <= 42) {
                if (bot.botTick % (TASK_TICK_DIV * 2) == 0) {
                    bot.takingTask();
                    System.out.println("[BOT-QUEST] bot=" + bot.id + " took task=" + bot.taskId);
                }
                return;
            }
            if (bot.taskMain == null) {
                return;
            }
            // Tiến độ nhiệm vụ: kill quest đếm theo đánh quái thật (bot đang farm)
            if (bot.botState == BotState.ATTACK && bot.botTick % 45 == 0) {
                // Mỗi ~22 giây farm -> +1 tiến độ (chậm hơn người thật)
                bot.updateTaskCount(1);
                bot.botNeeds.satisfy(BotNeeds.QUEST, 0.3);
            }
            // Đôi khi nhắc chuyện nhiệm vụ trong khu
            if (bot.botTick % (TASK_TICK_DIV * 3) == 0 && NinjaUtils.nextInt(0, 100) < 40
                    && bot.botProfile.talkativeness > 0.4f) {
                BotChat.chatQuest(bot);
            }
        } catch (Exception e) {
            Log.error("BotProgress tickTask err: " + e.getMessage(), e);
        }
    }

    // ================= NÂNG CẤP ĐỒ =================

    /**
     * Bot tự nâng cấp trang bị (theo personality COLLECTOR/GREEDY):
     * +1 một món equipment thỏa điều kiện, kèm chat phản ứng thành công/thất bại.
     * Không đi qua upgradeItem(packet) — bot tự cộng upgrade trực tiếp
     * trên item của chính mình (mặc định mọi bot đã có đá cường hóa ảo).
     */
    public static void tickUpgradeItem(AutoFarmBot bot) {
        if (bot == null || bot.equipment == null) {
            return;
        }
        try {
            // Chỉ 20% bot nâng đồ thường xuyên (COLLECTOR/GREEDY nhiều hơn)
            boolean eager = bot.botProfile.personalities.contains(BotPersonality.COLLECTOR)
                    || bot.botProfile.personalities.contains(BotPersonality.GREEDY);
            if (!eager && NinjaUtils.nextInt(0, 100) >= 20) {
                return;
            }
            // Tìm món có thể nâng
            Item target = null;
            for (Item eq : bot.equipment) {
                if (eq == null || eq.template == null) {
                    continue;
                }
                boolean upgradeable = eq.template.isTypeClothe() || eq.template.isTypeAdorn()
                        || eq.template.isTypeWeapon();
                if (upgradeable && eq.upgrade < eq.template.getUpMax()) {
                    target = eq;
                    break;
                }
            }
            if (target == null) {
                return;
            }
            // Tỷ lệ thành công như game thật (~65%), thất bại không mất đồ (bot an toàn)
            boolean success = NinjaUtils.nextInt(0, 100) < 65;
            if (success) {
                target.upgrade++;
                if (NinjaUtils.nextInt(0, 100) < 25 * bot.botProfile.talkativeness) {
                    BotChat.chatUpgrade(bot, target, true);
                }
                bot.botNeeds.satisfy(BotNeeds.GOLD, 0.4);
                System.out.println("[BOT-PROGRESSION] bot=" + bot.id + " upgrade OK +"
                        + target.upgrade + " " + target.template.name);
            } else if (NinjaUtils.nextInt(0, 100) < 15) {
                BotChat.chatUpgrade(bot, target, false);
            }
        } catch (Exception e) {
            Log.error("BotProgress tickUpgradeItem err: " + e.getMessage(), e);
        }
    }

    // ================= NHẶT ĐỒ HIẾM =================

    /**
     * Bot nhặt đồ -> nếu là đồ hiếm (upgrade cao / item đặc biệt) -> chat khoe.
     */
    public static void reactOnPickup(AutoFarmBot bot, Item item) {
        if (bot == null || item == null || item.template == null) {
            return;
        }
        try {
            boolean rare = item.upgrade >= 10
                    || (item.template.isTypeWeapon() && item.template.level >= bot.level + 5);
            if (rare && NinjaUtils.nextInt(0, 100) < 35 * bot.botProfile.talkativeness) {
                BotChat.chatRareDrop(bot, item);
            }
        } catch (Exception ignored) {
        }
    }

    // ================= CHẾT / HỒI SINH =================

    /** Bot vừa chết -> kêu than (nếu trả về cuối cùng hồi sinh). */
    public static void reactOnDeath(AutoFarmBot bot) {
        if (bot == null || bot.zone == null) {
            return;
        }
        try {
            if (NinjaUtils.nextInt(0, 100) < 30 * bot.botProfile.talkativeness) {
                BotChat.chatDeath(bot);
            }
        } catch (Exception ignored) {
        }
    }
}
