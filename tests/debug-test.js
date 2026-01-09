function testDebugJs(data) {
    console.log('Debug JS data:', data);

    return {
        ok: true,
        data: data
    };
}

testDebugJs({ a: 1, b: 2 });
