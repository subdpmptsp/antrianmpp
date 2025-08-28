async function getPrinter() {
    try {
        return await navigator.bluetooth.requestDevice({
            filters: [
                { namePrefix: "RPP" },
                { namePrefix: "Thermal" },
                { namePrefix: "POS" }
            ],
            optionalServices: ["000018f0-0000-1000-8000-00805f9b34fb"]
        })
    } catch (e) {
        console.error("Failed to connect printer", e)
    }
}

async function printThermal(text)
{
    try {
        if (!window.connectedPrinter) {
            throw new Error("Printer tidak tersedia")
        }
    
        const server = await window.connectedPrinter.gatt.connect()
    
        const service = await server.getPrimaryService("000018f0-0000-1000-8000-00805f9b34fb")
    
        const characteristic = await service.getCharacteristic("00002af1-0000-1000-8000-00805f9b34fb")
    
        const encoder = new TextEncoder()
        const data = encoder.encode(text)
    
        characteristic.writeValue(data)

        console.log("Success print", text)
    } catch (e) {
        console.error("Failed to print thermal", e)
    }

}