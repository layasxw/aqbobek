const data = require('./data.json');

const activities = data.activities;
const slots = data.timeslots;
const rooms = data.rooms;

function canPlace(activity, slot, room, schedule) {
    if (activity.requiredRoomType !== room.type) return false;

    for (let entry of schedule) {
        if (entry.timeSlotId === slot.id && entry.roomId === room.id) {
            return false;
        }
    }

    for (let entry of schedule) {
        if (entry.timeSlotId === slot.id) {
            const scheduledActivity = activities.find(a => a.id === entry.activityId);
            if (scheduledActivity.teacher === activity.teacher) return false;
            if (scheduledActivity.classGroup === activity.classGroup) return false;
        }
    }
    return true;
}


function scoreSlot(activity, slot, schedule, activities, rooms) {
  let score = 0;

  if (["Math", "Physics", "Chemistry"].includes(activity.subject) && slot.period < 3) {
    score += 2; 
  }
  if (activity.subject === "PE" && (slot.period >= 5 || slot.period < 3)) {
    score += 2; 
  }

  const teacherConflicts = schedule.filter(entry => {
    const scheduledActivity = activities.find(a => a.id === entry.activityId);
    return scheduledActivity.teacher === activity.teacher &&
           Math.abs(entry.timeSlotId - slot.id) === 1; 
  });
  if (teacherConflicts.length > 0) score += 1;

  const classConflicts = schedule.filter(entry => {
    const scheduledActivity = activities.find(a => a.id === entry.activityId);
    return scheduledActivity.classGroup === activity.classGroup &&
           Math.abs(entry.timeSlotId - slot.id) === 1;
  });
  if (classConflicts.length > 0) score += 1;

  const room = rooms.find(r => r.type === activity.requiredRoomType);
  if (room) score += 1; 

  if (activity.priority) score *= activity.priority;

  return score;
}

function generateSchedule(activities, timeslots, rooms) {
  const scheduleEntries = [];

  for (let activity of activities) {
    let bestSlot = null;
    let bestRoom = null;
    let bestScore = -1;

    for (let slot of timeslots) {
      for (let room of rooms) {
        if (canPlace(activity, slot, room, scheduleEntries)) {
          const score = scoreSlot(activity, slot, scheduleEntries, activities, rooms);
          if (score > bestScore) {
            bestScore = score;
            bestSlot = slot;
            bestRoom = room;
          }
        }
      }
    }

    if (bestSlot && bestRoom) {
      // ставим активность в расписание
      scheduleEntries.push({
        id: scheduleEntries.length + 1,
        activityId: activity.id,
        timeSlotId: bestSlot.id,
        roomId: bestRoom.id
      });
    } else {
      console.log(`Не удалось разместить: ${activity.subject} (${activity.classGroup})`);
    }
  }

  return scheduleEntries;
}


const schedule = generateSchedule(activities, slots, rooms);




async function getLLMSuggestions(activities, timeslots, rooms, schedule, condition) {
  const prompt = `
  У тебя есть:
  - Activities: ${JSON.stringify(activities)}
  - Timeslots: ${JSON.stringify(timeslots)}
  - Rooms: ${JSON.stringify(rooms)}
  - Current Schedule: ${JSON.stringify(schedule)}
  
  Нужно изменить расписание по условию: ${JSON.stringify(condition)}
  Предложи для каждого затронутого урока новое время и комнату, избегая конфликтов.
  Ответ дай в JSON формате:
  [{"activityId":1, "timeSlotId":3, "roomId":2}, ...]
  `;

  const response = await fetch("https://api.openai.com/v1/chat/completions", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "Authorization": `Bearer YOUR_API_KEY`
    },
    body: JSON.stringify({
      model: "gpt-4.1-mini",
      messages: [{role: "user", content: prompt}],
      max_tokens: 500
    })
  });

  const data = await response.json();
  const suggestions = JSON.parse(data.choices[0].message.content);
  return suggestions; 
}

async function rescheduleWithLLM(sickTeacher) {
  const condition = {teacher: sickTeacher};

  const suggestions = await getLLMSuggestions(activities, timeslots, rooms, schedule, condition);

  for (let sug of suggestions) {
    schedule = schedule.filter(e => e.activityId !== sug.activityId);
    schedule.push({
      id: schedule.length + 1,
      activityId: sug.activityId,
      timeSlotId: sug.timeSlotId,
      roomId: sug.roomId
    });
  }

  return schedule;
}

