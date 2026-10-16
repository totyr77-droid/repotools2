async function setStockQuantity(page,value){
 await page.locator('#stockAmount').click();
 const panel=page.locator('#stockCalculatorPanel');await panel.waitFor({state:'visible'});
 await panel.locator('[data-calc="C"]').click();
 for(const digit of String(value))await panel.locator('[data-calc="'+digit+'"]').click();
 await panel.locator('.stock-calculator-done').click();
}
module.exports={setStockQuantity};
