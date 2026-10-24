export default async function run(page, ui) {
  // Select worker
  await ui.click('@e19');
  await page.waitForTimeout(500);
  await page.keyboard.type('María González');
  await page.waitForTimeout(500);
  await page.keyboard.press('Enter');
  await page.waitForTimeout(1000);
  
  // Select state - Aprobada
  await page.selectOption('select[id="form.estado"]', 'Aprobada');
  await page.waitForTimeout(500);
  
  // Set start date - next month
  const start = new Date();
  start.setMonth(start.getMonth() + 1);
  start.setDate(1);
  const startStr = start.toISOString().split('T')[0];
  await page.fill('input[id="form.fecha_inicio"]', startStr);
  await page.waitForTimeout(500);
  
  // Set end date - 5 days later
  const end = new Date(start);
  end.setDate(end.getDate() + 4);
  const endStr = end.toISOString().split('T')[0];
  await page.fill('input[id="form.fecha_fin"]', endStr);
  await page.waitForTimeout(500);
  
  // Set days - 5
  await page.fill('input[id="form.dias_habiles"]', '5');
  await page.waitForTimeout(500);
  
  // Click Crear
  await page.click('button:has-text("Crear")');
  await page.waitForTimeout(4000);
  
  return await ui.snapshot();
}