const data = {
  users: [
    {id:1, name:"Ivanova Olga", role:"teacher"},
    {id:2, name:"Petrov Sergey", role:"teacher"},
    {id:3, name:"Sidorova Anna", role:"teacher"},
    {id:4, name:"Aliyev Timur", role:"student", classGroup:"7A"},
    {id:5, name:"Bekmuratova Aida", role:"student", classGroup:"7B"}
  ],
  classes: [
    {id:1, name:"7A"},
    {id:2, name:"7B"}
  ],
  subjects: [
    {id:1, name:"Math", requiredRoomType:"standard"},
    {id:2, name:"Physics", requiredRoomType:"lab"},
    {id:3, name:"Chemistry", requiredRoomType:"lab"},
    {id:4, name:"English", requiredRoomType:"standard"},
    {id:5, name:"PE", requiredRoomType:"gym"}
  ],
  rooms: [
    {id:1, name:"Room101", type:"standard"},
    {id:2, name:"Lab201", type:"lab"},
    {id:3, name:"Gym", type:"gym"}
  ],
  timeslots: [
    {id:1, day:"Mon", period:1},
    {id:2, day:"Mon", period:2},
    {id:3, day:"Mon", period:3},
    {id:4, day:"Mon", period:4},
    {id:5, day:"Tue", period:1},
    {id:6, day:"Tue", period:2},
    {id:7, day:"Tue", period:3},
    {id:8, day:"Tue", period:4}
  ],
  activities: [
    {id:1, subject:"Math", teacher:"Ivanova Olga", classGroup:"7A", duration:1, requiredRoomType:"standard", priority:1},
    {id:2, subject:"Physics", teacher:"Petrov Sergey", classGroup:"7A", duration:1, requiredRoomType:"lab", priority:1},
    {id:3, subject:"Chemistry", teacher:"Sidorova Anna", classGroup:"7B", duration:1, requiredRoomType:"lab", priority:1},
    {id:4, subject:"English", teacher:"Sidorova Anna", classGroup:"7A", duration:1, requiredRoomType:"standard", priority:1},
    {id:5, subject:"PE", teacher:"Ivanova Olga", classGroup:"7B", duration:1, requiredRoomType:"gym", priority:1}
  ]
};

const activities = data.activities;
const timeslots = data.timeslots;
const rooms = data.rooms;

function canPlace(activity, slot, room, schedule) {
  if (activity.requiredRoomType !== room.type) return false;
  for (let e of schedule) {
    if (e.timeSlotId === slot.id) {
      const a = activities.find(a => a.id === e.activityId);
      if (a.teacher === activity.teacher) return false;
      if (a.classGroup === activity.classGroup) return false;
      if (e.roomId === room.id) return false;
    }
  }
  return true;
}

function scoreSlot(activity, slot) {
  let score = 0;
  if (["Math","Physics","Chemistry"].includes(activity.subject) && slot.period < 3) score += 2;
  if (activity.subject === "PE" && slot.period > 3) score += 2;
  return score;
}

function generateSchedule(activities, timeslots, rooms) {
  const schedule = [];
  for (let activity of activities) {
    let bestSlot = null;
    let bestRoom = null;
    let bestScore = -1;
    for (let slot of timeslots) {
      for (let room of rooms) {
        if (canPlace(activity, slot, room, schedule)) {
          const score = scoreSlot(activity, slot);
          if (score > bestScore) {
            bestScore = score;
            bestSlot = slot;
            bestRoom = room;
          }
        }
      }
    }
    if (bestSlot && bestRoom) {
      schedule.push({
        id: schedule.length+1,
        activityId: activity.id,
        timeSlotId: bestSlot.id,
        roomId: bestRoom.id
      });
    } else {
      console.log(`Не удалось разместить: ${activity.subject} (${activity.classGroup})`);
    }
  }
  return schedule;
}

let schedule = generateSchedule(activities, timeslots, rooms);

const filterTypeSelect = document.getElementById("filter-type");
const filterValueSelect = document.getElementById("filter-value");
const container = document.getElementById("schedule-container");

function updateFilterValues() {
  const type = filterTypeSelect.value;
  filterValueSelect.innerHTML = "";
  let options = [];
  if (type==="class") options = [...new Set(activities.map(a=>a.classGroup))];
  if (type==="teacher") options = [...new Set(activities.map(a=>a.teacher))];
  if (type==="room") options = [...new Set(rooms.map(r=>r.name))];
  options.forEach(val=>{
    const option = document.createElement("option");
    option.value = val;
    option.innerText = val;
    filterValueSelect.appendChild(option);
  });
}

function renderSchedule(type, value) {
  container.innerHTML = "";
  const table = document.createElement("table");
  const header = table.insertRow();
  ["День","Период","Предмет","Учитель","Класс","Кабинет"].forEach(h=>{
    const cell = header.insertCell();
    cell.innerText = h;
  });

  const entries = schedule.filter(e=>{
    const act = activities.find(a=>a.id===e.activityId);
    const room = rooms.find(r=>r.id===e.roomId);
    if (type==="class") return act.classGroup===value;
    if (type==="teacher") return act.teacher===value;
    if (type==="room") return room.name===value;
  });

  entries.forEach(entry=>{
    const act = activities.find(a=>a.id===entry.activityId);
    const slot = timeslots.find(t=>t.id===entry.timeSlotId);
    const room = rooms.find(r=>r.id===entry.roomId);
    const row = table.insertRow();
    [slot.day, slot.period, act.subject, act.teacher, act.classGroup, room.name].forEach(v=>{
      const cell = row.insertCell();
      cell.innerText = v;
    });
  });

  container.appendChild(table);
}

updateFilterValues();
renderSchedule(filterTypeSelect.value, filterValueSelect.value);

filterTypeSelect.addEventListener("change", ()=>{
  updateFilterValues();
  renderSchedule(filterTypeSelect.value, filterValueSelect.value);
});

filterValueSelect.addEventListener("change", ()=>{
  renderSchedule(filterTypeSelect.value, filterValueSelect.value);
});