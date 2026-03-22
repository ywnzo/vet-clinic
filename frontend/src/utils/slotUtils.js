export function toMinutes(hm) {
  const [hours, minutes] = hm.split(':').map(Number);
  return hours * 60 + minutes;
}

export function toTimeString(minutes) {
  const hours = Math.floor(minutes / 60).toString().padStart(2, '0');
  const mins = (minutes % 60).toString().padStart(2, '0');
  return `${hours}:${mins}`;
}

export function generateSlots(officeStart = '10:00', officeEnd = '16:00', slotMinutes = 60) {
  const startMinutes = toMinutes(officeStart);
  const endMinutes = toMinutes(officeEnd);
  const slots = [];
  for (let t = startMinutes; t < endMinutes; t += slotMinutes) {
    slots.push(toTimeString(t));
  }
  return slots;
}
