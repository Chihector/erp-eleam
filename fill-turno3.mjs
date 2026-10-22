export default async function run(page, ui) {
  // Use selectOption directly on the selects
  await page.selectOption('select[id="form.tipo_turno"]', 'Día (08:00 a 20:00)');
  await page.waitForTimeout(500);
  
  await page.selectOption('select[id="form.estado"]', 'Programado');
  await page.waitForTimeout(500);
  
  // Set start date/time - tomorrow at 08:00
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  const tomorrowStr = tomorrow.toISOString().split('T')[0] + 'T08:00';
  await page.fill('input[id="form.fecha_inicio"]', tomorrowStr);
  await page.waitForTimeout(500);
  
  // Set end date/time - tomorrow at 20:00
  const tomorrowEndStr = tomorrow.toISOString().split('T')[0] + 'T20:00';
  await page.fill('input[id="form.fecha_fin"]', tomorrowEndStr);
  await page.waitForTimeout(500);
  
  // Click Crear
  await page.click('button:has-text("Crear")');
  await page.waitForTimeout(4000);
  
  return await ui.snapshot();
}