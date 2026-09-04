package nro.virtualplayer;

import nro.services.TaskService;
import nro.task.SubTaskMain;
import nro.task.TaskMain;

/**
 * Quest AI cho Virtual Player.
 * PHASE 5 - Quest.
 * Đọc nhiệm vụ hiện tại, hoàn thành qua TaskService (giống player),
 * điều hướng theo objective (mapId, đánh quái).
 */
public class VirtualQuest {

    private final VirtualPlayer vp;
    private long lastTimeAdvance;

    public VirtualQuest(VirtualPlayer vp) {
        this.vp = vp;
    }

    public TaskMain getCurrentTask() {
        if (vp.playerTask == null || vp.playerTask.taskMain == null) return null;
        return vp.playerTask.taskMain;
    }

    public SubTaskMain getCurrentSubTask() {
        TaskMain main = getCurrentTask();
        if (main == null || main.subTasks == null || main.index < 0 || main.index >= main.subTasks.size()) {
            return null;
        }
        return main.subTasks.get(main.index);
    }

    /**
     * Tiến triển nhiệm vụ hiện tại. Trả về true nếu đã gọi doneTask.
     */
    public boolean progress() {
        TaskMain main = getCurrentTask();
        if (main == null) return false;

        long now = System.currentTimeMillis();
        if (now - lastTimeAdvance < 15000) return false;
        lastTimeAdvance = now;

        try {
            int idTaskCustom = (main.id << 10) + main.index;
            idTaskCustom <<= 1;
            TaskService.gI().doneTask(vp, idTaskCustom);
            return true;
        } catch (Exception e) {
            return false;
        }
    }

    public int getObjectiveMap() {
        SubTaskMain sub = getCurrentSubTask();
        if (sub == null) return -1;
        return sub.mapId;
    }

    public boolean isSubTaskDone() {
        SubTaskMain sub = getCurrentSubTask();
        return sub != null && sub.count >= sub.maxCount;
    }

    public String describeCurrentTask() {
        SubTaskMain sub = getCurrentSubTask();
        if (sub == null) return "Không có nhiệm vụ";
        return sub.name + " (" + sub.count + "/" + sub.maxCount + ")";
    }
}
