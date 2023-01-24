import React, { memo } from 'react';
import { Handle } from 'reactflow';

export default memo(({ data, id }: any) => {
    return (
    <>
      <div className="border h-40 w-20 bg-white dark:bg-gray-800">
        {data.label}
      </div>
    </>
  );
});
